<?php


namespace CommonBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use NewApiBundle\Entity\Logs;

/**
 * Class LocationService
 * @package CommonBundle\Utils
 */
class LogService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var RequestValidator $requestValidator */
    private $requestValidator;

    /**
     * LocationService constructor.
     * @param EntityManagerInterface $entityManager
     * @param RequestValidator $requestValidator
     */
    public function __construct(EntityManagerInterface $entityManager, RequestValidator $requestValidator)
    {
        $this->em = $entityManager;
        $this->requestValidator = $requestValidator;
    }

    /**
     * @return Logs[]|null|object
     */
    public function getLogs()
    {
        return $this->em->getRepository(Logs::class)->findAll();
    }
}