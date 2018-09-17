<?php

namespace Tests\DistributionBundle\Controller;

use Tests\BMSServiceTestCase;
use DistributionBundle\Entity\DistributionData;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use BeneficiaryBundle\Entity\Beneficiary;

class DistributionCSVServiceTest extends BMSServiceTestCase
{

    public function setUp()
    {
        $this->setDefaultSerializerName('jms_serializer');
        parent::setUpFunctionnal();
    }

    /**
     * Test used to check if the function returns the right informations in each array.
     */
    public function testparseCSV()
    {
        $distributionCSVService = $this->container->get('distribution.distribution_csv_service');

        $countryIso3 = 'KHM';

        //distributionData will be used in the function "parseCSV" to get all the beneficiaries in a project :
        $distributionData = $this->em->getRepository(DistributionData::class)->findOneById('1');
        $beneficiariesInProject = $this->em->getRepository(Beneficiary::class)->getAllOfProject($distributionData->getProject()->getId());
        $distributionBeneficiaryService = $this->container->get('distribution.distribution_beneficiary_service');

        //beneficiaries contains all beneficiaries in a distribution :
        $beneficiaries = $distributionBeneficiaryService->getBeneficiaries($distributionData);
        $uploadedFile = new UploadedFile(__DIR__.'/../Resources/beneficiaryInDistribution.csv', 'beneficiaryInDistribution.csv');


        $jsonFromparseCSV = $distributionCSVService->parseCSV($countryIso3, $beneficiaries, $distributionData, $uploadedFile);


        $errorArray = $jsonFromparseCSV['errors'];
        $addArray = $jsonFromparseCSV['added'];
        $deleteArray = $jsonFromparseCSV['deleted'];

        if(!$beneficiaries && !$beneficiariesInProject){
            $this->assertTrue(count($errorArray) > 0);
        }
        elseif (!$beneficiaries && $beneficiariesInProject) {
            $this->assertTrue(count($addArray) > 0);
        }
        elseif ($beneficiaries && $beneficiariesInProject) {
            $this->assertTrue(count($errorArray) > 0 || count($addArray) > 0 || count($deleteArray) > 0);
        }
    }

    /**
     * Test used to check if the datas are saved and deleted from the database.
     */
    /*public function testsaveCSV()
    {
        $distributionCSVService = $this->container->get('distribution.distribution_csv_service');

        $countryIso3 = 'KHM';
        $distributionData = $this->em->getRepository(DistributionData::class)->findOneById('1');
        $uploadedFile = new UploadedFile(__DIR__.'/../Resources/beneficiaryInDistribution.csv', 'r');

        $beneficiaries = $this->em->getRepository(Beneficiary::class)->getAllofDistribution($distributionData);

        $distributionCSVService->saveCSV($countryIso3, $beneficiaries, $distributionData, $uploadedFile);



        //We check if the element that should be suppressed is suppressed :
        foreach ($beneficiaries as $beneficiary){
            if(($beneficiary->getGivenName() == "Test4" || $beneficiary->getGivenName() == "Test") && $beneficiary->getFamilyName() == "Tester"){
                $this->assertTrue(($beneficiary->getGivenName() == "Test4" || $beneficiary->getGivenName() == "Test") && $beneficiary->getFamilyName() == "Tester");
            }
            else{
                $this->assertFalse($beneficiary->getGivenName() == "Test6" && $beneficiary->getFamilyName() == "Bis");
            }
        }

        //We check if the element that should be added is added :
        foreach ($beneficiaries as $beneficiary){
            if($beneficiary->getGivenName() == "Test4" && $beneficiary->getFamilyName() == "Tester"){
                $this->assertTrue($beneficiary->getGivenName() == "Test4" && $beneficiary->getFamilyName() == "Tester");
                return true;
            }
        }
    }*/
}
