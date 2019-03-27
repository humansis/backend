<?php


namespace BeneficiaryBundle\Utils\DataVerifier;

use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractVerifier implements InterfaceVerifier
{

    /** @var EntityManagerInterface $em */
    protected $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }
}
