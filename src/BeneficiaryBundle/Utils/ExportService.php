<?php

namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Symfony\Component\DependencyInjection\ContainerInterface;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\Profile;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;



Class ExportService {

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /** @var Beneficiary $beneficiary */
    private $beneficiary;


    private $MAPPING_CSV_BENEFICIARY = [
        // Beneficiary
        "Given name" => $this->beneficiary->getGivenName(),
        "Family name" => $this->beneficiary->getFamilyName(),
        "Gender"=> $this->beneficiary->getGender() ,
        "Status"=> $this->beneficiary->getStatus(),
        "Date of birth"=> $this->beneficiary-> ,
        "Vulnerability criteria" => ,
        "Phones" => ,
        "National IDs" =>
    ];

//    private $MAPPING_CSV_HOUSEHOLD = [
//        // Household
//        "Given name" =>  ,
//        "Family name" => ,
//        "Gender"=> ,
//        "Status"=> ,
//        "Date of birth"=> ,
//        "Vulnerability criteria" => ,
//        "Phones" => ,
//        "National IDs" =>
//    ];

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;


     
    }

















}