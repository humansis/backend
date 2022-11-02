<?php

declare(strict_types=1);

namespace Component\LogsStorage;

use DateTime;
use League\Flysystem\FilesystemException;
use Component\Storage\Aws\AwsStorage;
use Factory\AwsStorageFactory;
use Factory\LogsStorageConfigFactory;
use Utils\FileSystem\PathConstructor;
use Utils\FileSystem\Zip;
use Symfony\Component\HttpFoundation\File\File;
use Entity\User;
use Entity\Vendor;

class LogsStorageService
{
    private readonly \Component\Storage\Aws\AwsStorage $aws;

    public function __construct(
        private readonly string $vendorLogPathTemple,
        private readonly string $fieldLogPathTemplate,
        private readonly int $logsLifetime,
        LogsStorageConfigFactory $logsStorageFactory,
        AwsStorageFactory $awsStorageFactory
    ) {
        $this->aws = $awsStorageFactory->create($logsStorageFactory->create());
    }

    /**
     * @param        $file
     * @throws FilesystemException
     */
    private function upload(string $fileName, $file): string
    {
        $path = 'test/' . $fileName;

        return $this->aws->upload($path, $file);
    }

    /**
     *
     * @throws FilesystemException
     */
    public function uploadVendorApp(Vendor $vendor, File $file)
    {
        $extractedZip = Zip::extractToTempDir($file);

        $path = PathConstructor::construct($this->vendorLogPathTemple, [
            'email' => $vendor->getUser()->getEmail(),
            'vendorId' => $vendor->getId(),
            'datetime' => (new DateTime())->format('Y-m-d_H:i:s'),
        ]);

        foreach ($extractedZip as $fileFromZip => $extractedPath) {
            $this->aws->upload($path . $fileFromZip, file_get_contents($extractedPath));
        }
    }

    /**
     *
     * @throws FilesystemException
     */
    public function uploadFieldApp(User $user, File $file)
    {
        $extractedZip = Zip::extractToTempDir($file);

        $path = PathConstructor::construct($this->fieldLogPathTemplate, [
            'email' => $user->getEmail(),
            'userId' => $user->getId(),
            'datetime' => (new DateTime())->format('Y-m-d_H:i:s'),
        ]);

        foreach ($extractedZip as $fileFromZip => $extractedPath) {
            $this->aws->upload($path . $fileFromZip, file_get_contents($extractedPath));
        }
    }

    /**
     * @throws FilesystemException
     *
     * @returns array of deleted files
     */
    public function clearOldLogs(): array
    {
        $deleteBefore = (new DateTime())->modify("- $this->logsLifetime days");

        $oldLogs = $this->aws->listModifiedBefore($deleteBefore);

        $deletedLogsPath = [];

        foreach ($oldLogs as $oldLog) {
            $this->aws->delete($oldLog->path());

            $deletedLogsPath[] = $oldLog->path();
        }

        return $deletedLogsPath;
    }
}
