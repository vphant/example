<?php

namespace App\Service\Importer;

use App\Command\ImportCommand;

class ImporterBag
{
    /**
     * @var ImporterInterface[]
     */
    protected $importers = [];

    /**
     * @param UserImporter $userImporter
     * @param CompanyImporter $companyImporter
     * @param Companyi18nImporter $companyi18nImporter
     */
    public function __construct(
        UserImporter $userImporter,
        CompanyImporter $companyImporter,
        Companyi18nImporter $companyi18nImporter
    ) {
        $this->add(ImportCommand::DOMAIN_USER, $userImporter);
        $this->add(ImportCommand::DOMAIN_COMPANY, $companyImporter);
        $this->add(ImportCommand::DOMAIN_COMPANYI18N, $companyi18nImporter);
    }

    /**
     * @param string $alias
     * @return ImporterInterface
     * @throws \Exception
     */
    public function get(string $alias)
    {
        if (!isset($this->importers[$alias])) {
            throw new \Exception(
                sprintf('Unknown importer "%s". Do you forget to register importer in ImporterBag?', $alias)
            );
        }

        return $this->importers[$alias];
    }

    /**
     * @param string $alias
     * @param ImporterInterface $importer
     */
    protected function add(string $alias, ImporterInterface $importer)
    {
        $this->importers[$alias] = $importer;
    }

}
