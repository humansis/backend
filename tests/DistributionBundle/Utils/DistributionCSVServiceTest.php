<?php

namespace Tests\DistributionBundle\Controller;

use Tests\BMSServiceTestCase;
use DistributionBundle\Entity\DistributionData;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DistributionCSVServiceTest extends BMSServiceTestCase
{
    /** @var LocationService $locationService */
    private $locationService;

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

        $countryIso3 = 'FR';
        $distributionData = $this->em->getRepository(DistributionData::class)->findOneById('1');
        $distributionBeneficiaryService = $this->container->get('distribution.distribution_beneficiary_service');
        $beneficiaries = $distributionBeneficiaryService->getBeneficiaries($distributionData);
        $uploadedFile = new UploadedFile(__DIR__.'/../Resources/beneficiaryInDistribution.csv', 'r');

        $jsonFromparseCSV = $distributionCSVService->parseCSV($countryIso3, $beneficiaries, $distributionData, $uploadedFile);

        $errorArray = $jsonFromparseCSV['errors'];
        $addArray = $jsonFromparseCSV['added'];
        $deleteArray = $jsonFromparseCSV['deleted'];

        for ($i = 0; $i < count($errorArray); ++$i) {
            $this->assertTrue($errorArray[$i]['givenName'] == 'UserLambda' && $errorArray[$i]['familyName'] == 'FamilyLambda');
        }

        for ($i = 0; $i < count($addArray); ++$i) {
            $this->assertTrue($addArray[$i]['givenName'] == 'Test4' && $addArray[$i]['familyName'] == 'Tester');
        }

        for ($i = 0; $i < count($deleteArray); ++$i) {
            $this->assertTrue($deleteArray[$i]['givenName'] == 'Test6' && $deleteArray[$i]['familyName'] == 'Bis');
        }
    }

    public function testsaveCSV()
    {
        $distributionCSVService = $this->container->get('distribution.distribution_csv_service');

        $countryIso3 = 'FR';
        $distributionData = $this->em->getRepository(DistributionData::class)->findOneById('1');
        $distributionBeneficiaryService = $this->container->get('distribution.distribution_beneficiary_service');
        $beneficiaries = $distributionBeneficiaryService->getBeneficiaries($distributionData);
        $uploadedFile = new UploadedFile(__DIR__.'/../Resources/beneficiaryInDistribution.csv', 'r');

        $jsonFromparseCSV = $distributionCSVService->parseCSV($countryIso3, $beneficiaries, $distributionData, $uploadedFile);

        $errorArray = $jsonFromparseCSV['errors'];
        $addArray = $jsonFromparseCSV['added'];
        $deleteArray = $jsonFromparseCSV['deleted'];

        for ($i = 0; $i < count($errorArray); ++$i) {
            $this->assertTrue($errorArray[$i]['givenName'] == 'UserLambda' && $errorArray[$i]['familyName'] == 'FamilyLambda');
        }

        for ($i = 0; $i < count($addArray); ++$i) {
            $this->assertTrue($addArray[$i]['givenName'] == 'Test4' && $addArray[$i]['familyName'] == 'Tester');
        }

        for ($i = 0; $i < count($deleteArray); ++$i) {
            $this->assertTrue($deleteArray[$i]['givenName'] == 'Test6' && $deleteArray[$i]['familyName'] == 'Bis');
        }
    }
}
