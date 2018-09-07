<?php

namespace App\Service\Uploader;

use App\Service\AmazonClient;
use MongoDB\Collection;

class LogoUploader extends AbstractUploader
{
    /**
     * @inheritdoc
     */
    protected function getPrefix(): string
    {
        return AmazonClient::PREFIX_LOGOS;
    }

    /**
     * @inheritdoc
     */
    protected function getItems(int $limit): array
    {
        $this->dm->getFilterCollection()->disable('public');

        return $this->dm->getRepository('App:Company')->getCompanyOldLogos($limit);
    }

    /**
     * @inheritdoc
     */
    protected function getCollection(): Collection
    {
        return $this->mongoNativeClient->getCollection('company');
    }

    /**
     * @inheritdoc
     */
    protected function updateItem(Collection $collection, array $item, $result)
    {
        $collection->updateOne(
            ['_id' => (int)$item['_id']],
            ['$set' => ['logo' => $result]]
        );
    }

    /**
     * @inheritdoc
     */
    protected function getName(): string
    {
        return 'logo';
    }

    /**
     * @inheritdoc
     */
    protected function getUrl(array $item): string
    {
        return $item['oldLogo'];
    }

    /**
     * @inheritdoc
     */
    protected function generateNewName(array $item, string $ext): string
    {
        return $item['_id'] . '.' . $ext;
    }

}
