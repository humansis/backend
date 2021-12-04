<?php declare(strict_types=1);

namespace NewApiBundle\Component\LogsStorage;

use League\Flysystem\FilesystemException;
use NewApiBundle\Component\Storage\IStorage;
use NewApiBundle\Component\Storage\IStorageConfig;
use NewApiBundle\Factory\AwsStorageFactory;
use NewApiBundle\Factory\LogsStorageConfigFactory;

class LogsStorageService
{

    /**
     * @var string
     */
    private $folder;

    /**
     * @var AwsStorageFactory
     */
    private $awsStorageFactory;

    /**
     * @var IStorageConfig
     */
    private $logsStorageConfig;

    /**
     * @var IStorage
     */
    private $aws;

    public function __construct(
        string                   $folder,
        LogsStorageConfigFactory $logsStorageFactory,
        AwsStorageFactory        $awsStorageFactory
    ) {
        $this->folder = $folder;
        $this->awsStorageFactory = $awsStorageFactory;
        $this->logsStorageConfig = $logsStorageFactory->create();
        $this->aws = $this->awsStorageFactory->create($this->logsStorageConfig);
    }

    /**
     * @param string $fileName
     * @param        $file
     *
     * @return string
     * @throws FilesystemException
     */
    private function upload(string $fileName, $file): string
    {
        $path = $this->folder.'/'.$fileName;

        return $this->aws->upload($path, $file);
    }
}
