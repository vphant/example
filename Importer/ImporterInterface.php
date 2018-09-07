<?php

namespace App\Service\Importer;

use Symfony\Component\Console\Output\OutputInterface;

interface ImporterInterface
{
    /**
     * Import documents from migration api
     * @param OutputInterface $output
     * @param int $pages
     */
    public function import(OutputInterface $output, int $pages);
}
