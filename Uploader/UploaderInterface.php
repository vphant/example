<?php

namespace App\Service\Uploader;

use Symfony\Component\Console\Output\OutputInterface;

interface UploaderInterface
{
    /**
     * @param OutputInterface $output
     * @param int $limit
     */
    public function upload(OutputInterface $output, $limit);
}
