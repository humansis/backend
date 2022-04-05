<?php declare(strict_types=1);

namespace NewApiBundle\Component\LogsStorage;

use DateTime;
use League\Flysystem\FilesystemException;
use NewApiBundle\Component\Storage\Aws\AwsStorage;
use NewApiBundle\Factory\AwsStorageFactory;
use NewApiBundle\Factory\LogsStorageConfigFactory;
use NewApiBundle\Utils\FileSystem\PathConstructor;
use NewApiBundle\Utils\FileSystem\Zip;
use Symfony\Component\HttpFoundation\File\File;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Vendor;

class LogsStorageService
{
    /**
     * @var AwsStorage
     */
    private $aws;

    /**
     * @var string
     */
    private $vendorLogPathTemple;

    /**
     * @var string
     */
    private $fieldLogPathTemplate;

    /**
     * @var int
     */
    private $logsLifetime;

    public function __construct(
        string $vendorLogPathTemplate,
        string $filedLogPathTemplate,
        int $logsLifetime,
        LogsStorageConfigFactory $logsStorageFactory,
        AwsStorageFactory        $awsStorageFactory
    ) {
        $this->vendorLogPathTemple = $vendorLogPathTemplate;
        $this->fieldLogPathTemplate = $filedLogPathTemplate;
        $this->logsLifetime = $logsLifetime;

        $this->aws = $awsStorageFactory->create($logsStorageFactory->create());
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
        $path = 'test/'.$fileName;

        return $this->aws->upload($path, $file);
    }

    /**
     * @param Vendor $vendor
     * @param File   $file
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
     * @param User $user
     * @param File $file
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
