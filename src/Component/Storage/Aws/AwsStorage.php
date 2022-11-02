<?php

declare(strict_types=1);

namespace Component\Storage\Aws;

use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;
use DateTime;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Component\Storage\IStorage;
use Component\Storage\IStorageConfig;
use Component\Storage\StorageEnum;

class AwsStorage implements IStorage
{
    private \Aws\S3\S3ClientInterface $client;

    private \League\Flysystem\FilesystemAdapter $adapter;

    private \League\Flysystem\FilesystemOperator $filesystem;

    public function __construct(private IStorageConfig $storageConfig)
    {
        $this->client = new S3Client($storageConfig->getOptions());
        $this->adapter = new AwsS3V3Adapter($this->client, $storageConfig->getBucketName());
        $this->filesystem = new Filesystem($this->adapter);
    }

    /**
     * @param        $file
     *
     * @throws FilesystemException
     */
    public function upload(string $path, $file, string $visibility = StorageEnum::PRIVATE_S): string
    {
        $this->filesystem->write($path, $file, [StorageEnum::VISIBILITY => $visibility]);

        return $path;
    }

    public function list(string $path): iterable
    {
        return $this->filesystem->listContents($path, true);
    }

    /**
     * @return FileAttributes[]
     * @throws FilesystemException
     *
     */
    public function listModifiedBefore(DateTime $time): iterable
    {
        $list = $this->filesystem->listContents('', true);

        /** @var FileAttributes $item */
        foreach ($list as $item) {
            if ((new DateTime())->setTimestamp($item->lastModified()) < $time) {
                yield $item;
            }
        }
    }

    /**
     * @throws FilesystemException
     */
    public function delete(string $filePath): bool
    {
        $this->filesystem->delete($filePath);

        return true;
    }

    public function getStorageConfig(): IStorageConfig
    {
        return $this->storageConfig;
    }

    public function setStorageConfig(IStorageConfig $storageConfig): void
    {
        $this->storageConfig = $storageConfig;
    }

    /**
     * @return S3ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    public function setClient(S3ClientInterface $client): void
    {
        $this->client = $client;
    }

    /**
     * @return FilesystemAdapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    public function setAdapter(FilesystemAdapter $adapter): void
    {
        $this->adapter = $adapter;
    }

    /**
     * @return FilesystemOperator
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    public function setFilesystem(FilesystemOperator $filesystem): void
    {
        $this->filesystem = $filesystem;
    }
}
