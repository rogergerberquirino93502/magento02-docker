<?php
namespace Google\Cloud\Model\MediaStorage\File\Storage;

// use Aws\S3\Exception\S3Exception;
use Magento\Framework\DataObject;
use Google\Cloud\ServiceBuilder;
class Gcs extends DataObject
{
    /**
     * Store media base directory path
     *
     * @var string
     */
    protected $mediaBaseDirectory = null;

    private $client;

    private $helper;

    /**
     * Core file storage database
     *
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     */
    private $storageHelper;

    /**
     * @var \Magento\MediaStorage\Helper\File\Media
     */
    private $mediaHelper;

    /**
     * Collect errors during sync process
     *
     * @var string[]
     */
    private $errors = [];

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

	private $storageFile;
	
	
    private $objects = [];

    public function __construct(
        \Google\Cloud\Helper\Data $helper,
        \Magento\MediaStorage\Helper\File\Media $mediaHelper,
        \Magento\MediaStorage\Helper\File\Storage\Database $storageHelper,
        \Psr\Log\LoggerInterface $logger,
		\Magento\MediaStorage\Model\File\Storage\File $storageFile
    ) {
        parent::__construct();

        $this->helper = $helper;
        $this->mediaHelper = $mediaHelper;
        $this->storageHelper = $storageHelper;
		$this->storageFile = $storageFile;
        $this->logger = $logger;
		$this->helper->getAccessKey();
		$json_key = $this->helper->getAccessKey();
        $key_array = json_decode( $json_key,true );
        $project = $key_array['project_id'];
        //$this->client = new StorageClient([
        //$gcloud = new \Google\Cloud\ServiceBuilder(array('projectId'=> $project,'keyFile'=> $json_key));
        
        $this->client = new \cAc\GcsWrapper\GoogleCloudStorage(
				$project,
        		$json_key,
        		$this->helper->getBucket()
        	);
    }

    /**
     * Initialisation
     *
     * @return $this
     */
    public function init() {
    
        return $this;
    
    }

    /**
     * Return storage name
     *
     * @return \Magento\Framework\Phrase
     */
    public function getStorageName() {
    
        return __('Google Cloud Storage');
    
    }

    /**
     * @param string $filename
     * @return $this
     */
    public function loadByFilename($filename) {
    
//        	Need to set a download location; file can only be downloaded, then contents, then should be unlinked.
// 			Perhaps __DIR__ ? 
// 			Right now, we're going to error out every time
        //$location = ?????;
        $fail = false;
        try {
        	$object_is = $this->client->object_exists( $filename );
        	if( $object_is ) {
        	
				$object = $this->client->object_download( $location );
            
            }
            else {
            
                $fail = true;
                
            }
            $contents = file_get_contents( $location );
            if ($object['Body']) {
                $this->setData('id', $filename);
                $this->setData('filename', $filename);
                $this->setData('content', (string) $contents);
                //unlink( $filename );
            } else {
                $fail = true;
            }
        }
        catch (\Exception $e) {
        
            $fail = true;
        
        }

        if ($fail) {
        
            $this->unsetData();
       
       }
        return $this;
    }

    /**
     * @return bool
     */
    public function hasErrors() {
    
        return !empty($this->errors);
    
    }

    public function clear() {
    
// 		$batch = \Aws\S3\BatchDelete::fromListObjects($this->client, [
// 			'Bucket' => $this->getBucket()
// 		]);
// 		$batch->delete();
        return $this;
    
    }

    public function exportDirectories($offset = 0, $count = 100) {
    
        return false;
    
    }

    public function importDirectories(array $dirs = []) {
    
        return $this;
    
    }

    /**
     * Retrieve connection name
     *
     * @return null
     */
    public function getConnectionName() {
    
        return null;
    
    }

    public function exportFiles($offset = 0, $count = 100) {
		
        $files = $this->storageFile->exportFiles($offset,$count);
		
//			So, $this->client->bucket_get_objects() returns a generator, and I don't know what the Interface allows... it's in the google client (a dependency of the wrapper), so it should be able to be tracked down, but in order to rewrite the iteration below, you'll need to find how the Generator is written first.
//			Essentially, this goes through the files in the bucket and returns them all in an array, including the contents for whatever reason.

//         if (empty($this->objects)) {
//             $this->objects = $this->client->listObjects([
//                 'Bucket' => $this->getBucket(),
//                 'MaxKeys' => $count
//             ]);
//         } else {
//             $this->objects = $this->client->listObjects([
//                 'Bucket' => $this->getBucket(),
//                 'MaxKeys' => $count,
//                 'Marker' => $this->objects[count($this->objects) - 1]
//             ]);
//         }
// 
//         if (empty($this->objects)) {
//             return false;
//         }
// 
//         foreach ($this->objects as $object) {
//             if (isset($object['Contents']) && substr($object['Contents'], -1) != '/') {
//                 $content =  $this->client->getObject([
//                     'Bucket' => $this->getBucket(),
//                     'Key' => $object['Key']
//                 ]);
//                 if (isset($content['Body'])) {
//                     $files[] = [
//                         'filename' => $object['Key'],
//                         'content' => (string) $content['Body']
//                     ];
//                 }
//             }
//         }

        return $files;
    }

    public function importFiles( array $files = [] )
    {
		
    	$json_key = $this->helper->getAccessKey();
        $key_array = json_decode( $json_key,true );
        $project = $key_array['project_id'];
        foreach( $files as $file ) {        
            try {
				$mediaPath = $file['directory'].'/'.$file['filename'];
                $this->client->bucket_upload_object(
                	$mediaPath,
					$this->storageHelper->getMediaBaseDir(),
                	$file['filename'],
                	false,
                	"publicRead"
                );
            } 
            catch (\Exception $e) {
            
                $this->errors[] = $e->getMessage();
                $this->logger->critical($e);
            
            }
        
        }

        return $this;
    }

    public function saveFile($filename)
    {
        $file = $this->mediaHelper->collectFileInfo($this->getMediaBaseDirectory(), $filename);

//         try {
//             $this->client->putObject([
//                 'ACL' => 'public-read',
//                 'Body' => $file['content'],
//                 'Bucket' => $this->getBucket(),
//                 'ContentType' => \GuzzleHttp\Psr7\mimetype_from_filename($file['filename']),
//                 'Key' => $filename
//             ]);
//         } catch (\Exception $e) {
//         }

        return $this;
    }

    public function fileExists($filename)
    {
//         return $this->client->doesObjectExist($this->getBucket(), $filename);
    }

    public function copyFile($oldFilePath, $newFilePath)
    {
//         try {
//             $this->client->copyObject([
//                 'Bucket' => $this->getBucket(),
//                 'Key' => $newFilePath,
//                 'CopySource' => $this->getBucket() . '/' . $oldFilePath,
//                 'ACL' => 'public-read'
//             ]);
//         } catch (S3Exception $e) {
//         }
        return $this;
    }

    public function renameFile($oldFilePath, $newFilePath)
    {
//         try {
//             $this->client->copyObject([
//                 'Bucket' => $this->getBucket(),
//                 'Key' => $newFilePath,
//                 'CopySource' => $this->getBucket() . '/' . $oldFilePath,
//                 'ACL' => 'public-read'
//             ]);
// 
//             $this->client->deleteObject([
//                 'Bucket' => $this->getBucket(),
//                 'Key' => $oldFilePath
//             ]);
//         } catch (S3Exception $e) {
//         }
        return $this;
    }

    /**
     * Delete file from Amazon S3
     *
     * @param string $path
     * @return $this
     */
    public function deleteFile($path)
    {
//         try {
//             $this->client->deleteObject([
//                 'Bucket' => $this->getBucket(),
//                 'Key' =>  $path
//             ]);
//         } catch (S3Exception $e) {
//         }

        return $this;
    }

    public function getSubdirectories($path)
    {
        $subdirectories = [];

        $prefix = $this->storageHelper->getMediaRelativePath($path);
        $prefix = rtrim($prefix, '/') . '/';

//         $objects = $this->client->listObjects([
//             'Bucket' => $this->getBucket(),
//             'Prefix' => $prefix,
//             'Delimiter' => '/'
//         ]);
// 
//         if (isset($objects['CommonPrefixes'])) {
//             foreach ($objects['CommonPrefixes'] as $object) {
//                 if (!isset($object['Prefix'])) {
//                     continue;
//                 }
// 
//                 $subdirectories[] = [
//                     'name' => $object['Prefix']
//                 ];
//             }
//         }

        return $subdirectories;
    }

    public function getDirectoryFiles($path)
    {
        $files = [];

        $prefix = $this->storageHelper->getMediaRelativePath($path);
        $prefix = rtrim($prefix, '/') . '/';

//         $objects = $this->client->listObjects([
//             'Bucket' => $this->getBucket(),
//             'Prefix' => $prefix,
//             'Delimiter' => '/'
//         ]);
// 
//         if (isset($objects['Contents'])) {
//             foreach ($objects['Contents'] as $object) {
//                 if (isset($object['Key']) && $object['Key'] != $prefix) {
//                     $content = $this->client->getObject([
//                         'Bucket' => $this->getBucket(),
//                         'Key' => $object['Key']
//                     ]);
//                     if (isset($content['Body'])) {
//                         $files[] = [
//                             'filename' => $object['Key'],
//                             'content' =>(string) $content['Body']
//                         ];
//                     }
//                 }
//             }
//         }

        return $files;
    }

    public function deleteDirectory($path)
    {
        $mediaRelativePath = $this->storageHelper->getMediaRelativePath($path);
        $prefix = rtrim($mediaRelativePath, '/') . '/';

//         $this->client->deleteMatchingObjects($this->getBucket(), $prefix);

        return $this;
    }

    protected function getBucket()
    {
        return $this->helper->getBucket();
    }

    /**
     * Retrieve media base directory path
     *
     * @return string
     */
    public function getMediaBaseDirectory()
    {
        if (is_null($this->mediaBaseDirectory)) {
            $this->mediaBaseDirectory = $this->storageHelper->getMediaBaseDir();
        }
        return $this->mediaBaseDirectory;
    }
}
