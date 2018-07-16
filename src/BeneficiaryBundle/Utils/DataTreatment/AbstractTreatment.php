<?php


namespace BeneficiaryBundle\Utils\DataTreatment;


use BeneficiaryBundle\Utils\BeneficiaryService;
use BeneficiaryBundle\Utils\HouseholdService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Container;

abstract class AbstractTreatment implements InterfaceTreatment
{

    protected $em;

    protected $householdService;

    protected $beneficiaryService;

    public function __construct(EntityManagerInterface $entityManager, HouseholdService $householdService, BeneficiaryService $beneficiaryService)
    {
        $this->em = $entityManager;
        $this->householdService = $householdService;
        $this->beneficiaryService = $beneficiaryService;
    }
}