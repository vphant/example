<?php

namespace App\Service\Importer;

use App\Service\MigrationApiClient;
use Carbon\Carbon;
use Doctrine\MongoDB\Collection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractImporter implements ImporterInterface
{
    const DEFAULT_LIMIT = 150;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var MigrationApiClient
     */
    protected $migrationApiClient;

    /**
     * @param DocumentManager $dm
     * @param MigrationApiClient $migrationApiClient
     */
    public function __construct(DocumentManager $dm, MigrationApiClient $migrationApiClient)
    {
        $this->dm = $dm;
        $this->migrationApiClient = $migrationApiClient;
    }

    /**
     * @inheritdoc
     */
    public function import(OutputInterface $output, int $pages)
    {
        $this->preImport();
        $this->purgeCollection($output);

        $totalPages = $this->getTotalPages($pages);

        $page = 1;
        $totalImported = 0;
        $totalSkipped = 0;
        $output->writeln('');
        $output->writeln(sprintf('Importing %s', $this->getName()));

        $progressBar = new ProgressBar($output, $totalPages);
        $progressBar->start();

        while ($page <= $totalPages) {
            $data = $this->migrationApiClient->getData(
                $this->getUri(),
                self::DEFAULT_LIMIT,
                ($page - 1) * self::DEFAULT_LIMIT
            );

            foreach ($data['objects'] as $object) {
                $result = $this->importItem($object);
                if (true === $result) {
                    $totalImported++;
                } else {
                    $totalSkipped++;
                }
            }
            $progressBar->advance();
            $page++;
        }

        $this->dm->flush();
        $progressBar->finish();
        $output->writeln('');
        $output->writeln(sprintf('[OK] %d %s imported', $totalImported, $this->getName()));
        $output->writeln(sprintf('%d %s skipped', $totalSkipped, $this->getName()));
    }

    /**
     * Import item from migration api to DB
     * Returns true if item import succeed
     * Returns false if item import fails
     * @param array $item
     * @return bool
     */
    abstract protected function importItem(array $item): bool;

    /**
     * @param int $pages
     * @return int
     */
    protected function getTotalPages($pages)
    {
        $data = $this->migrationApiClient->getData($this->getUri(), 1, 0);
        $total = $data['meta']['total_count'];

        $countPages = (int)ceil($total / self::DEFAULT_LIMIT);

        $totalPages = $countPages;
        if (0 < $pages && $pages < $countPages) {
            $totalPages = $pages;
        }

        return $totalPages;
    }

    /**
     * @param OutputInterface $output
     */
    protected function purgeCollection(OutputInterface $output)
    {
        $output->writeln('Purge collection');
        $collection = $this->getCollection();
        $collection->remove([]);
        $this->dm->flush();
        $this->dm->clear();
    }

    /**
     * @param $string
     * @return null|Carbon
     */
    protected function parseDate($string)
    {
        if (empty($string)) {
            return null;
        }

        $array = explode('.', $string, 2);
        $clear = $array[0];

        return Carbon::createFromFormat('Y-m-d H:i:s', str_replace('T', ' ', $clear));
    }

    /**
     * @param mixed $string
     * @return array
     */
    protected function parseArray($string)
    {
        if (is_array($string)) {
            return $string;
        }

        $clear = str_replace(['[', ']', ' '], '', trim($string));
        if (empty($clear)) {
            return [];
        }

        return explode(',', $clear);
    }

    protected function preImport()
    {
        // actions before import starts
    }

    /**
     * Get migration api uri
     * @return string
     */
    abstract protected function getUri(): string;

    /**
     * Get importer name
     * @return string
     */
    abstract protected function getName(): string;

    /**
     * @return Collection
     */
    abstract protected function getCollection(): Collection;
}
