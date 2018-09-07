<?php

namespace App\Service\Uploader;

use App\Service\AmazonClient;
use MongoDB\Collection;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class SliderUploader extends AbstractUploader
{
    /**
     * Redefine upload function for sliders because embedded collection is used.
     * If we have more of the same cases, then we should make an abstract class e.g. EmbeddedUploader for handling
     * embedded collections and move the common logic there.
     *
     * @param OutputInterface $output
     * @param int $limit
     */
    public function upload(OutputInterface $output, $limit)
    {
        $prefix = $this->getPrefix();
        $this->amazonClient->clearFolder($prefix);
        $output->writeln(sprintf('%s S3 folder cleared [OK]', $prefix));

        $companies = $this->getItems($limit);

        $totalCompaniesHandled = 0;
        $totalHandled = 0;
        $totalUploaded = 0;
        $totalSkipped = 0;

        $output->writeln('');
        $output->writeln(sprintf('Uploading to S3. Prefix: %s', $prefix));

        $progressBar = new ProgressBar($output, 0 == $limit ? count($companies) : $limit);
        $progressBar->start();

        $collection = $this->getCollection();

        foreach ($companies as $company) {
            $totalCompaniesHandled++;
            foreach ($company['sliders'] as $index => $item) {
                $totalHandled++;
                $item['index'] = $index;
                $item['companyId'] = $company['_id'];

                $result = $this->uploadItem($item);
                if (null === $result) {
                    $totalSkipped++;
                } else {
                    $totalUploaded++;
                }
                $this->updateItem($collection, $item, $result);
            }

            unset($company);

            $progressBar->advance();
        }

        $progressBar->finish();
        $output->writeln('');
        $output->writeln(sprintf('%d companies handled', $totalCompaniesHandled));
        $output->writeln(sprintf('%d handled', $totalHandled));
        $output->writeln(sprintf('%d uploaded [OK]', $totalUploaded));
        $output->writeln(sprintf('%d skipped', $totalSkipped));
    }

    /**
     * @inheritdoc
     */
    protected function getPrefix(): string
    {
        return AmazonClient::PREFIX_SLIDER;
    }

    /**
     * @inheritdoc
     */
    protected function getItems(int $limit): array
    {
        $this->dm->getFilterCollection()->disable('public');

        return $this->dm->getRepository('App:Company')->getCompanySliders($limit);
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
            ['_id' => (int)$item['companyId']],
            ['$set' => [sprintf('sliders.%d.image', $item['index']) => $result]]
        );
    }

    /**
     * @param array $item
     * @param string $url
     * @return string
     */
    protected function getMessageSkipped(array $item, string $url): string
    {
        return sprintf(
            '[upload - skipped][%s] Company Id: %d. Id: %d. Url: %s ',
            $this->getName(),
            $item['companyId'],
            $item['_id'],
            $url
        );
    }

    /**
     * @inheritdoc
     */
    protected function getName(): string
    {
        return 'slider';
    }

    /**
     * @inheritdoc
     */
    protected function getUrl(array $item): string
    {
        return $this->escapeUrl(AbstractUploader::MEDIA_SOURCE . '/media/' . $item['oldImage']);
    }

    /**
     * @inheritdoc
     */
    protected function generateNewName(array $item, string $ext): string
    {
        return sprintf('%s-%s.%s', $item['companyId'], $item['_id'], $ext);
    }

}
