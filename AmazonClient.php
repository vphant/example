<?php

namespace App\Service;

use Aws\S3\S3Client;
use Symfony\Component\Filesystem\Filesystem;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\CachingStream;

class AmazonClient
{
    const PREFIX_TEST = 'imgix/test/';
    const PREFIX_LOGOS = 'imgix/logos/';
    const PREFIX_SLIDER = 'imgix/slider/';

    /**
     * @var S3Client $s3Client
     */
    protected $s3Client;

    /**
     * @var array
     */
    protected $params;

    /**
     * @param S3Client $s3Client
     * @param array $params
     */
    public function __construct(S3Client $s3Client, $params)
    {
        $this->s3Client = $s3Client;
        $this->params = $params;
    }

    /**
     * Get bucket location to test if access to amazon s3 bucket works
     * @return array
     */
    public function getBucketLocation()
    {
        return $this->s3Client->getBucketLocation(['Bucket' => $this->params['bucket']])->toArray();
    }

    /**
     * Upload a test image to bucket 'imgix/test/' folder
     * @return array
     */
    public function uploadTestImage()
    {
        $this->clearFolder(self::PREFIX_TEST);

        $hash = uniqid();
        print_r('Hash: ' . $hash);

        $filesystem = new Filesystem();
        $filesystem->mkdir('/tmp/tmpfile/');

        $tempFilePath = '/tmp/tmpfile/' . $hash . '.jpg';
        $tempFile = fopen($tempFilePath, "w") or die("Error: Unable to open file.");
        $fileContents = file_get_contents('https://picsum.photos/200?random');
        file_put_contents($tempFilePath, $fileContents);

        $result = $this->uploadFile(self::PREFIX_TEST . $hash . '.jpg', $tempFilePath);

        return $result->toArray();
    }

    /**
     * @param string $key
     * @param string $sourceFile
     * @return \Aws\Result
     */
    public function uploadFile($key, $sourceFile)
    {
        return $this->s3Client->putObject(
            [
                'ACL' => 'public-read',
                'Bucket' => $this->params['bucket'],
                'Key' => $key,
                'SourceFile' => $sourceFile,
                'StorageClass' => 'REDUCED_REDUNDANCY',
            ]
        );
    }

    /**
     * @param string $key
     * @param string $url
     * @param string $contentType
     * @return \Aws\Result
     */
    public function uploadFileByUrl($key, $url, $contentType)
    {
        $result = $this->s3Client->putObject(
            [
                'Body' => new CachingStream(new Stream(@fopen($url, 'r'))),
                'ACL' => 'public-read',
                'Bucket' => $this->params['bucket'],
                'Key' => $key,
                'StorageClass' => 'REDUCED_REDUNDANCY',
                'ContentType' => $contentType,
            ]
        );

        return $result;
    }

    /**
     * Delete folder and it's content on s3 storage.
     * Use with care!
     * @param string $folder
     */
    public function clearFolder($folder)
    {
        $this->deleteListObjects($folder, null);
    }

    /**
     * @param string $folder
     * @param string|null $nextToken
     */
    protected function deleteListObjects($folder, $nextToken)
    {
        $list = $this->s3Client->listObjectsV2(
            [
                'Bucket' => $this->params['bucket'],
                'Prefix' => $folder,
                'NextContinuationToken' => $nextToken,
            ]
        );

        if (0 == $list->get('KeyCount')) {
            return;
        }

        $this->s3Client->deleteObjects(
            [
                'Bucket' => $this->params['bucket'],
                'Delete' => ['Objects' => $list->get('Contents')],
            ]
        );

        if (true === $list->get('IsTruncated')) {
            $this->deleteListObjects($folder, $list->get('NextContinuationToken'));
        }
    }

}
