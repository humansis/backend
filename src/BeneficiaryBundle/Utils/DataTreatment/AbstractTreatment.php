<?php


namespace BeneficiaryBundle\Utils\DataTreatment;


use BeneficiaryBundle\Utils\BeneficiaryService;
use BeneficiaryBundle\Utils\HouseholdService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Container;

abstract class AbstractTreatment implements InterfaceTreatment
{

    /** @var EntityManagerInterface $em */
    protected $em;

    /** @var HouseholdService $householdService */
    protected $householdService;

    /** @var BeneficiaryService $beneficiaryService */
    protected $beneficiaryService;

    /** @var $token */
    protected $token;

    /** @var Container $container */
    protected $container;

    public function __construct(
        EntityManagerInterface $entityManager,
        HouseholdService $householdService,
        BeneficiaryService $beneficiaryService,
        Container $container,
        &$token
    )
    {
        $this->em = $entityManager;
        $this->householdService = $householdService;
        $this->beneficiaryService = $beneficiaryService;
        $this->container = $container;
        $this->token = $token;
    }
}