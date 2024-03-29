<?php

declare(strict_types=1);

namespace Component\File;

use Aws\S3\Exception\S3Exception;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadService
{
    public function __construct(private readonly FilesystemMap $filesystemMap, private readonly string $bucketName, private readonly string $region)
    {
    }

    /**
     *
     * @return string URL of file
     *
     * @throws Exception\UploadException
     */
    public function upload(UploadedFile $uploadedFile, string $filesystem)
    {
        $filename = sprintf('%s.%s', uniqid(), $uploadedFile->getClientOriginalExtension());

        try {
            $adapter = $this->filesystemMap->get($filesystem)->getAdapter();
            $adapter->setMetadata('Content-Type', $uploadedFile->getMimeType());
            $adapter->write($filename, file_get_contents($uploadedFile->getPathname()));

            return 'https://s3.' . $this->region . '.amazonaws.com/' . $this->bucketName . '/' . $filesystem . '/' . $filename;
        } catch (S3Exception $ex) {
            throw new  Exception\UploadException('Upload to AWS S3 failed.', $ex);
        }
    }
}
