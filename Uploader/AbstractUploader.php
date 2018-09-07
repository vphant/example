<?php

namespace App\Service\Uploader;

use App\Service\AmazonClient;
use App\Service\MongoNativeClient;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Collection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractUploader implements UploaderInterface
{
    const MEDIA_SOURCE = 'https://example.com';

    /**
     * @var AmazonClient
     */
    protected $amazonClient;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var MongoNativeClient
     */
    protected $mongoNativeClient;

    /**
     * @param AmazonClient $amazonClient
     * @param DocumentManager $dm
     * @param LoggerInterface $logger
     * @param MongoNativeClient $mongoNativeClient
     */
    public function __construct(
        AmazonClient $amazonClient,
        DocumentManager $dm,
        LoggerInterface $logger,
        MongoNativeClient $mongoNativeClient
    ) {
        $this->amazonClient = $amazonClient;
        $this->dm = $dm;
        $this->logger = $logger;
        $this->mongoNativeClient = $mongoNativeClient;
    }

    /**
     * @param OutputInterface $output
     * @param int $limit
     */
    public function upload(OutputInterface $output, $limit)
    {
        $prefix = $this->getPrefix();
        $this->amazonClient->clearFolder($prefix);
        $output->writeln(sprintf('%s S3 folder cleared [OK]', $prefix));

        $items = $this->getItems($limit);

        $totalHandled = 0;
        $totalUploaded = 0;
        $totalSkipped = 0;

        $output->writeln('');
        $output->writeln(sprintf('Uploading to S3. Prefix: %s', $prefix));

        $progressBar = new ProgressBar($output, 0 == $limit ? count($items) : $limit);
        $progressBar->start();

        $collection = $this->getCollection();

        foreach ($items as $item) {
            $totalHandled++;
            $result = $this->uploadItem($item);
            if (null === $result) {
                $totalSkipped++;
            } else {
                $totalUploaded++;
            }
            $this->updateItem($collection, $item, $result);

            unset($item);

            $progressBar->advance();
        }

        $progressBar->finish();
        $output->writeln('');
        $output->writeln(sprintf('%d handled', $totalHandled));
        $output->writeln(sprintf('%d uploaded [OK]', $totalUploaded));
        $output->writeln(sprintf('%d skipped', $totalSkipped));
    }

    /**
     * Returns new name if success upload
     * Returns null if fails to upload
     * @param array $item
     * @return string|null
     */
    final protected function uploadItem(array $item)
    {
        $url = $this->getUrl($item);
        $ext = $this->getExtension($url);

        $messageSkipped = $this->getMessageSkipped($item, $url);

        $contentType = $this->getContentType($ext);
        if (false === $contentType) {
            $this->logger->error(sprintf('%s Unsupported file type %s', $messageSkipped, $ext));

            return null;
        }

        $newName = $this->generateNewName($item, $ext);

        try {
            $result = $this->amazonClient->uploadFileByUrl(
                $this->getPrefix() . $newName,
                $url,
                $contentType
            );
        } catch (\InvalidArgumentException $e) {
            $this->logger->error(sprintf('%s Error on download', $messageSkipped));

            return null;
        }

        $statusCode = $result->get('@metadata')['statusCode'];

        if (200 == $statusCode) {
            // file was uploaded to S3
            return $newName;
        }

        $this->logger->error(sprintf('%s Error on upload. Status code: %s', $messageSkipped, $statusCode));

        return null;
    }

    /**
     * @param string $url
     * @return bool|string
     */
    protected function getExtension($url)
    {
        return strtolower(substr($url, strrpos($url, '.') + 1));
    }

    /**
     * Generate a new file name for an item
     * @param array $item
     * @param string $ext
     * @return string
     */
    abstract protected function generateNewName(array $item, string $ext): string;

    /**
     * Get prefix on amazon S3 folder
     * @return string
     */
    abstract protected function getPrefix(): string;

    /**
     * Get items for upload
     * @param int $limit
     * @return array
     */
    abstract protected function getItems(int $limit): array;

    /**
     * Get mongodb collection
     * @return Collection
     */
    abstract protected function getCollection(): Collection;

    /**
     * Update a single document in mongodb collection
     * @param Collection $collection
     * @param array $item
     * @param mixed $result
     */
    abstract protected function updateItem(Collection $collection, array $item, $result);

    /**
     * Get uploader name
     * @return string
     */
    abstract protected function getName(): string;

    /**
     * Get source file url
     * @param array $item
     * @return string
     */
    abstract protected function getUrl(array $item): string;

    /**
     * @param string $ext
     * @return string|bool
     */
    protected function getContentType($ext)
    {
        $map = [
            'pdf' => 'application/pdf',
            'art' => 'image/x-jg',
            'bm' => 'image/bmp',
            'bmp' => 'image/bmp',
            'dwg' => 'image/vnd.dwg',
            'dxf' => 'image/vnd.dwg',
            'fif' => 'image/fif',
            'flo' => 'image/florian',
            'fpx' => 'image/vnd.fpx',
            'g3' => 'image/g3fax',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'ief' => 'image/ief',
            'iefs' => 'image/ief',
            'jfif' => 'image/jpeg',
            'jfif-tbnl' => 'image/jpeg',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'jps' => 'image/x-jps',
            'jut' => 'image/jutvision',
            'mcf' => 'image/vasa',
            'nap' => 'image/naplps',
            'naplps' => 'image/naplps',
            'nif' => 'image/x-niff',
            'niff' => 'image/x-niff',
            'pbm' => 'image/x-portable-bitmap',
            'pct' => 'image/x-pict',
            'pcx' => 'image/x-pcx',
            'pgm' => 'image/x-portable-greymap',
            'pic' => 'image/pict',
            'pict' => 'image/pict',
            'pm' => 'image/x-xpixmap',
            'png' => 'image/png',
            'pnm' => 'image/x-portable-anymap',
            'ppm' => 'image/x-portable-pixmap',
            'qif' => 'image/x-quicktime',
            'qti' => 'image/x-quicktime',
            'qtif' => 'image/x-quicktime',
            'ras' => 'image/cmu-raster',
            'rast' => 'image/cmu-raster',
            'rf' => 'image/vnd.rn-realflash',
            'rgb' => 'image/x-rgb',
            'rp' => 'image/vnd.rn-realpix',
            'svf' => 'image/vnd.dwg',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'turbot' => 'image/florian',
            'wbmp' => 'image/vnd.wap.wbmp',
            'xbm' => 'image/x-xbitmap',
            'xif' => 'image/vnd.xiff',
            'xpm' => 'image/x-xpixmap',
            'x-png' => 'image/png',
            'xwd' => 'image/x-xwd',
            'webp' => 'image/webp',
            'eps' => 'image/x-eps',
        ];

        if (!isset($map[$ext])) {
            return false;
        }

        return $map[$ext];
    }

    /**
     * @param string s$url
     * @return string
     */
    final protected function escapeUrl($url)
    {
        $parts = parse_url($url);
        $pathParts = array_map('rawurldecode', explode('/', $parts['path']));

        return
            $parts['scheme'] . '://' .
            $parts['host'] .
            implode('/', array_map('rawurlencode', $pathParts));
    }

    /**
     * @param array $item
     * @param string $url
     * @return string
     */
    protected function getMessageSkipped(array $item, string $url): string
    {
        return sprintf('[upload - skipped][%s] Id: %d. Url: %s ', $this->getName(), $item['_id'], $url);
    }
}
