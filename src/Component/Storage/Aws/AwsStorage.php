<?php declare(strict_types=1);

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
    /**
     * @var IStorageConfig
     */
    private $storageConfig;

    /**
     * @var S3ClientInterface
     */
    private $client;

    /**
     * @var FilesystemAdapter
     */
    private $adapter;

    /**
     * @var FilesystemOperator
     */
    private $filesystem;

    public function __construct(IStorageConfig $storageConfig)
    {
        $this->storageConfig = $storageConfig;
        $this->client = new S3Client($storageConfig->getOptions());
        $this->adapter = new AwsS3V3Adapter($this->client, $storageConfig->getBucketName());
        $this->filesystem = new Filesystem($this->adapter);
    }

    /**
     * @param string $path
     * @param        $file
     * @param string $visibility
     *
     * @return string
     * @throws FilesystemException
     */
    public function upload(string $path, $file, string $visibility = StorageEnum::PRIVATE): string
    {
        $this->filesystem->write($path, $file, [StorageEnum::VISIBILITY => $visibility]);

        return $path;
    }

    public function list(string $path): iterable
    {
        return $this->filesystem->listContents($path, true);
    }

    /**
     * @throws FilesystemException
     *
     * @return FileAttributes[]
     */
    public function listModifiedBefore(DateTime $time): iterable
    {
        $list = $this->filesystem->listContents('', true);

        /** @var FileAttributes $item */
        foreach ($list as $item) {
            if ( (new DateTime())->setTimestamp($item->lastModified()) < $time ) {
                yield $item;
            }
        }
    }

    /**
     * @param string $filePath
     *
     * @return bool
     * @throws FilesystemException
     */
    public function delete(string $filePath): bool
    {
        $this->filesystem->delete($filePath);

        return true;
    }

    /**
     * @return IStorageConfig
     */
    public function getStorageConfig(): IStorageConfig
    {
        return $this->storageConfig;
    }

    /**
     * @param IStorageConfig $storageConfig
     */
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

    /**
     * @param S3ClientInterface $client
     */
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

    /**
     * @param FilesystemAdapter $adapter
     */
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

    /**
     * @param FilesystemOperator $filesystem
     */
    public function setFilesystem(FilesystemOperator $filesystem): void
    {
        $this->filesystem = $filesystem;
    }
}
