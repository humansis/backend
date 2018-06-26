<?php

namespace DistributionBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DistributionService {

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var ValidatorInterface $validator */
    private $validator;


    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer, ValidatorInterface $validator)
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }
}