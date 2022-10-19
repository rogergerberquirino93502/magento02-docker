<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\Adapter;

use Exception;
use Google_Client;
use Google_Exception;
use Google_Service_Exception;
use Google_Service_Sheets;
use Google_Service_Sheets_BatchUpdateSpreadsheetRequest;
use Google_Service_Sheets_Request;
use Google_Service_Sheets_Sheet;
use Google_Service_Sheets_ValueRange;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * GoogleSheet Adapter
 * Requires composer packages google/apiclient:^2.0
 *
 * phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @link https://developers.google.com/sheets/api/quickstart/php
 */
class GoogleSheet extends AbstractAdapter
{
    /**
     * Api constants
     */
    const DIMENSION_ROWS = 'ROWS';
    const DIMENSION_COLS = 'COLUMNS';

    /**
     * Cache storage
     *
     * @var CacheInterface
     */
    protected $_cache;

    /**
     * Directory list
     *
     * @var DirectoryList
     */
    protected $_directoryList;

    /**
     * Sheets Api
     *
     * @var Google_Service_Sheets
     */
    protected $_sheetsService;

    /**
     * Count of rows to push to the Google Sheet by single API request.
     *
     * @see GoogleSheet::getBatchSize()
     * @var int
     */
    protected $_batchMaxSize = 1000;

    /**
     * Starting cell template
     *
     * @see GoogleSheet::getRange()
     * @var string
     */
    protected $_range = 'A%s:%s';

    /**
     * Current cursor position. Used to append data to the document
     *
     * @var int
     */
    protected $_linesCounter = 1;

    /**
     * Rows prepared to be sent to the Google Sheet
     *
     * @var array
     */
    protected $_exportQueue = [];

    /**
     * JSON string with configuration for API authorization
     *
     * @var string
     */
    protected $_authConfig;

    /**
     * Google sheet id
     *
     * @var string
     */
    protected $_sheetId;

    /**
     * Google spreadsheet id
     *
     * @var string
     */
    protected $_spreadsheetId;

    /**
     * Access token
     *
     * @var string
     */
    protected $_accessToken;

    /**
     * Sheet columns count
     *
     * @var int
     */
    protected $_currentColumnsCount;

    /**
     * Sheet rows count
     *
     * @var int
     */
    protected $_currentRowsCount;

    /**
     * Sheet title
     *
     * @var string
     */
    protected $_sheetTitle;

    /**
     * GoogleSheet constructor.
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     * @param CacheInterface $cache
     * @param null $destination
     * @param string $destinationDirectoryCode
     * @param array $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Filesystem $filesystem,
        LoggerInterface $logger,
        DirectoryList $directoryList,
        CacheInterface $cache,
        $destination = null,
        $destinationDirectoryCode = \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,
        array $data = []
    ) {
        $this->_directoryList = $directoryList;
        $this->_cache = $cache;

        $this->_authConfig = $this->getAuthConfig($data['export_source']['signing_key_file_path']);
        $this->_accessToken = $this->getAccessToken();
        $this->_spreadsheetId = $data['export_source']['spreadsheet_id'];
        $this->_sheetId = $data['export_source']['sheet_id'];

        parent::__construct($filesystem, $logger, $destination, $destinationDirectoryCode, $data);
    }

    /**
     * Get auth config
     *
     * @param string $filePath
     * @return string
     * @throws RuntimeException
     */
    protected function getAuthConfig($filePath)
    {
        $rootPath = $this->_directoryList->getRoot();
        $absolutePath = $rootPath . DIRECTORY_SEPARATOR . $filePath;
        if (!file_exists($absolutePath)) {
            throw new RuntimeException('Auth file ' . $filePath . ' isn\'t readable or the file has been removed');
        }

        return file_get_contents($absolutePath);
    }

    /**
     * Get access token
     *
     * @return string
     * @throws RuntimeException
     */
    protected function getAccessToken()
    {
        $authConfig = json_decode($this->_authConfig, true);
        if (empty($authConfig['private_key_id'])) {
            throw new RuntimeException('Auth file doesn\'t contain all necessary settings to get Auth Token');
        }

        return $authConfig['private_key_id'];
    }

    /**
     * Collect items into batches and only then run single api request
     *
     * @throws Google_Exception
     * @throws Exception
     * @inheritdoc
     */
    public function writeRow(array $rowData)
    {
        if ($this->_headerCols === null) {
            $this->_headerCols = array_keys($rowData);
            $this->_exportQueue[] = $this->_headerCols;
        }

        $this->_exportQueue[] = array_values($rowData);
        if (count($this->_exportQueue) && count($this->_exportQueue) >= $this->getBatchSize()) {
            $this->runExportQueue();
        }

        return $this;
    }

    /**
     * Run export queue
     *
     * @throws Exception
     */
    protected function runExportQueue()
    {
        if (!count($this->_exportQueue)) {
            // Nothing to export
            return;
        }

        try {
            $this->exportQueue();
        } catch (Exception $exception) {
            if ($exception instanceof Google_Service_Exception) {
                $reason = $exception->getErrors()[0]['reason'];
                if ($reason == 'rateLimitExceeded') {
                    // Try to do short delay before run api requests again
                    $this->doDelay(10);
                }
            }

            throw $exception;
        }
    }

    /**
     * Export rows in queue to the specified sheet
     *
     * @throws Google_Exception
     */
    protected function exportQueue()
    {
        if ($this->_linesCounter == 1) {
            $this->fetchSheetInformation();
            $this->clearTable();

            $this->prepareTableColumns(count($this->_headerCols));
        }

        $this->prepareTableRows(count($this->_exportQueue));

        $this->insertRows();
        $this->postExportQueue();
    }

    /**
     * Fetch target table information
     *
     * @throws Google_Exception
     */
    protected function fetchSheetInformation()
    {
        /** @var Google_Service_Sheets_Sheet[] $sheets */
        $sheets = $this->getApi()->spreadsheets->get($this->_spreadsheetId);
        $sheet = $this->getSheetById($this->_sheetId, $sheets);

        $this->_currentColumnsCount = $sheet->getProperties()->getGridProperties()->getColumnCount();
        $this->_currentRowsCount = $sheet->getProperties()->getGridProperties()->getRowCount();
        $this->_sheetTitle = $sheet->getProperties()->getTitle();

        $this->doDelay();
    }

    /**
     * Get target sheet by sheetId
     *
     * @param string $sheetId
     * @param Google_Service_Sheets_Sheet[] $sheets
     * @return Google_Service_Sheets_Sheet
     * @throws RuntimeException
     */
    protected function getSheetById($sheetId, $sheets)
    {
        $neededSheet = null;
        foreach ($sheets as $sheet) {
            if ($sheet->getProperties()->getSheetId() == $sheetId) {
                $neededSheet = $sheet;
                break;
            }
        }

        if ($neededSheet === null) {
            throw new RuntimeException('Failed to find sheet #' . $sheetId);
        }

        return $neededSheet;
    }

    /**
     * Reset table to empty document state with 1 ros and column.
     * Google gives only 5000000 cells(~40000 products) per all tabs in document
     *
     * @throws Google_Exception
     */
    protected function clearTable()
    {
        $requests = [
            new Google_Service_Sheets_Request([
                'deleteDimension' => [
                    'range' => [
                        'sheetId' => $this->_sheetId,
                        'startIndex' => 0,
                        'endIndex' => $this->_currentRowsCount - 1,
                        'dimension' => self::DIMENSION_ROWS
                    ]
                ]
            ]),
            new Google_Service_Sheets_Request([
                'deleteDimension' => [
                    'range' => [
                        'sheetId' => $this->_sheetId,
                        'startIndex' => 0,
                        'endIndex' => $this->_currentColumnsCount - 1,
                        'dimension' => self::DIMENSION_COLS
                    ]
                ]
            ]),
        ];

        $this->getApi()->spreadsheets->batchUpdate(
            $this->_spreadsheetId,
            new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                'requests' => $requests
            ])
        );

        $this->_currentRowsCount = 1;
        $this->_currentColumnsCount = 1;

        $this->doDelay();
    }

    /**
     * In case if table has not enough cols we need to add them
     *
     * @param int $neededColumnsCount
     * @throws Google_Exception
     */
    protected function prepareTableColumns($neededColumnsCount)
    {
        if ($this->_currentColumnsCount >= $neededColumnsCount) {
            return;
        }

        $requests = [
            new Google_Service_Sheets_Request([
                'insertDimension' => [
                    'range' => [
                        'sheetId' => $this->_sheetId,
                        'startIndex' => $this->_currentColumnsCount - 1,
                        'endIndex' => $neededColumnsCount - 1,
                        'dimension' => self::DIMENSION_COLS
                    ]
                ]
            ])
        ];

        $this->getApi()->spreadsheets->batchUpdate(
            $this->_spreadsheetId,
            new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                'requests' => $requests
            ])
        );

        $this->_currentColumnsCount = $neededColumnsCount;
        $this->doDelay();
    }

    /**
     * In case if table has not enough rows we need to add them
     *
     * @param int $rowsToAdd
     * @throws Google_Exception
     */
    protected function prepareTableRows($rowsToAdd)
    {
        if ($this->_linesCounter + $rowsToAdd < $this->_currentRowsCount) {
            return;
        }

        $startIndex = $this->_linesCounter - 1;
        $requests = [
            new Google_Service_Sheets_Request([
                'insertDimension' => [
                    'range' => [
                        'sheetId' => $this->_sheetId,
                        'startIndex' => $startIndex,
                        'endIndex' => $startIndex + $rowsToAdd,
                        'dimension' => self::DIMENSION_ROWS
                    ]
                ]
            ])
        ];

        $this->getApi()->spreadsheets->batchUpdate(
            $this->_spreadsheetId,
            new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                'requests' => $requests
            ])
        );

        $this->_currentRowsCount += $rowsToAdd;
        $this->doDelay();
    }

    /**
     * Insert rows to the table
     *
     * @throws Google_Exception
     */
    protected function insertRows()
    {
        $options = ['valueInputOption' => 'RAW'];
        $body = new Google_Service_Sheets_ValueRange(['values' => $this->_exportQueue]);

        $this->getApi()
            ->spreadsheets_values
            ->update($this->_spreadsheetId, $this->getRange(), $body, $options);
    }

    /**
     * Get api client to work with sheet values
     *
     * @return Google_Service_Sheets
     * @throws Google_Exception
     */
    protected function getApi()
    {
        if ($this->_sheetsService === null) {
            $client = new Google_Client();
            $client->setApplicationName('');
            $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
            $client->setAuthConfig(json_decode($this->_authConfig, true));
            $client->setAccessToken($this->_accessToken);

            $this->_sheetsService = new Google_Service_Sheets($client);
        }

        return $this->_sheetsService;
    }

    /**
     * Get entities count
     *
     * @return int
     */
    public function getEntitiesCount()
    {
        return $this->_cache->load('export_entities_count');
    }

    /**
     * Get num rows per API request
     *
     * @return int
     */
    protected function getBatchSize()
    {
        $entitiesCount = $this->getEntitiesCount();
        if ($this->_batchMaxSize > $entitiesCount) {
            return $entitiesCount;
        }

        return $this->_batchMaxSize;
    }

    /**
     * Get current cell number
     *
     * @return string
     */
    protected function getRange()
    {
        $columnId = $this->getNameFromNumber($this->_currentColumnsCount);
        $cellRange = sprintf($this->_range, $this->_linesCounter, $columnId);

        return $this->_sheetTitle . '!' . $cellRange;
    }

    /**
     * Get google sheet column a1 notation string
     *
     * @param int $num
     * @return string
     */
    protected function getNameFromNumber(int $num)
    {
        $numeric = $num % 26;
        $codePoint = 65 + $numeric;
        if ($codePoint < 65) {
            $codePoint = 65;
        } elseif ($codePoint > 90) {
            $codePoint = 90;
        }
        $letter = chr($codePoint);
        $num2 = $num / 26;

        if ($num2 > 0) {
            return $this->getNameFromNumber((int)$num2 - 1) . $letter;
        } else {
            return $letter;
        }
    }

    /**
     * Clear queue, memory, etc
     */
    protected function postExportQueue()
    {
        $this->_linesCounter += count($this->_exportQueue);
        $this->_exportQueue = [];
    }

    /**
     * Google Sheets API has limitation 100 requests per 100 seconds
     *
     * @param int $seconds
     */
    protected function doDelay($seconds = 1)
    {
        sleep($seconds);
    }

    /**
     * Should return something or the log file would have not information
     *
     * @see \Firebear\ImportExport\Model\Export::export()
     * @inheritdoc
     * @throws Exception
     */
    public function getContents()
    {
        $this->runExportQueue();
        return '1';
    }
}
