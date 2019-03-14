<?php

namespace CommonBundle\Utils;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\ORM\EntityManager;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\AwsS3;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\Credentials\Credentials;


class UploadService implements ContainerAwareInterface
{

    private $container;
    private $s3;
 
    protected $aws_access_key_id;
    protected $aws_secret_access_key;
    protected $aws_s3_region;


    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
 
    /**
     * @param $aws_access_key_id
     * @param $aws_secret_access_key
     */
    public function __construct($aws_access_key_id, $aws_secret_access_key, $aws_s3_region, ContainerInterface $container)
    {
        $this->container = $container;

        $credentials = new Credentials(
            $aws_access_key_id,
            $aws_secret_access_key
        );
 
        // Instantiate the S3 client with your AWS credentials
        $s3 = S3Client::factory(array(
            'credentials' => $credentials,
            'version' => 'latest', //@TODO: not this in production
            'region' => $aws_s3_region
        ));
 
        $this->s3 = $s3;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @param \Gaufrette\Adapter\AwsS3 $adapter
     *
     * @return mixed
     * @throws \Exception
     */
    public function uploadImage(UploadedFile $file, AwsS3 $adapter) {

        try {

            $filename = sprintf('%s.%s', uniqid(), $file->getClientOriginalExtension());
            $adapter->setMetadata('Content-Type', $file->getMimeType());
            $response = $adapter->write($filename, file_get_contents($file->getPathname()));
            return $filename;

        }
        catch(S3Exception $e) {
            throw $e;
        }
        catch(\Exception $e) {
            throw $e;
        }
    }

}
