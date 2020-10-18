<?php


namespace NewApiBundle\Model;


use Doctrine\ORM\EntityManagerInterface;

class AssistanceService
{
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

}