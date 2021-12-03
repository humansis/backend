<?php declare(strict_types=1);

namespace NewApiBundle\Component\LogsStorage;

use League\Flysystem\FilesystemException;
use NewApiBundle\Component\Storage\Aws\AwsStorageFactory;
use NewApiBundle\Component\Storage\IStorageConfig;

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

    public function __construct(
        string                   $folder,
        LogsStorageConfigFactory $logsStorageFactory,
        AwsStorageFactory        $awsStorageFactory
    ) {
        $this->folder = $folder;
        $this->awsStorageFactory = $awsStorageFactory;
        $this->logsStorageConfig = $logsStorageFactory->create();
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
        $aws = $this->awsStorageFactory->create($this->logsStorageConfig);

        return $aws->upload($path, $file);
    }
}
