<?php

namespace CommonBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class DefaultController
 * @package CommonBundle\Controller
 */
class DefaultController extends Controller
{
    /** @var KernelInterface */
    private $kernel;

    /**
     * DefaultController constructor.
     *
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @Rest\Get("/version", name="api_version")
     *
     * @SWG\Tag(name="Version")
     *
     * @SWG\Response(
     *     response=200,
     *     description="The Api Version"
     * )
     *
     * @return Response
     */
    public function getVersion()
    {
        $rootDir = $this->kernel->getRootDir();

        $composerJsonLocation = sprintf('%s/../composer.json', $rootDir);

        $content = file_get_contents($composerJsonLocation);

        $composer = json_decode($content, true);

        return new Response($composer['version']);
    }
}
