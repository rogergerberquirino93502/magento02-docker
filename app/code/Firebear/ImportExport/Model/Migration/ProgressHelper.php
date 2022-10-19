<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

declare(strict_types=1);

namespace Firebear\ImportExport\Model\Migration;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Output\OutputInterface;

class ProgressHelper
{
    /**
     * @var ProgressBar
     */
    protected $progressBar;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param ProgressBarFactory $progressBarFactory
     * @param OutputInterface $output
     * @param string $name
     * @param int $max
     */
    public function __construct(
        ProgressBarFactory $progressBarFactory,
        OutputInterface $output,
        string $name,
        int $max
    ) {
        $this->output = $output;

        $this->progressBar = $progressBarFactory->create([
            'output' => $output,
            'max' => $max,
        ]);
        $this->progressBar->setFormat('<comment>%message%</comment> %current%/%max% [%bar%] %percent:3s%% %elapsed%');
        $this->progressBar->setMessage($name);
        $this->progressBar->display();
    }

    /**
     * @param int $count
     */
    public function advance($count)
    {
        $this->progressBar->advance($count);
    }

    /**
     * @return void
     */
    public function finish()
    {
        $this->progressBar->finish();
        $this->output->writeln('');
    }
}
