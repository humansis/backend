<?php

namespace Tests\DistributionBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Entity\DistributionData;
use Tests\BMSServiceTestCase;

class DistributionBeneficiaryServiceTest extends BMSServiceTestCase
{

    public function setUp()
    {
        $this->setDefaultSerializerName('jms_serializer');
        parent::setUpFunctionnal();
    }

    /**
     * Test used to check if the function returns the right informations in each array.
     * @test
     */
    /*public function removeBeneficiaryInDistributionTest(){

        //We check if there is an user in the DistributionBeneficiary before to delete him :
        $distributionBeneficiary = $this->em->getRepository(DistributionBeneficiary::class)->findOneBy(['beneficiary' => 1, 'distributionData' => 1]);

        // If there is no user, we create a new one :
        $distributionBeneficiaryService = $this->container->get('distribution.distribution_beneficiary_service');
        if(!$distributionBeneficiary){
            $distributionData = $this->em->getRepository(DistributionData::class)->find(1);
            $distributionBeneficiaryService->addBeneficiary($distributionData, array('id' => 1));
        }

        $distributionBeneficiary = $this->em->getRepository(DistributionBeneficiary::class)->findOneBy(['beneficiary' => 1, 'distributionData' => 1]);

        if($distributionBeneficiary){
            $this->assertTrue($distributionBeneficiary instanceof DistributionBeneficiary);
        }

        //We run the function to test :
        $beneficiary = $this->em->getRepository(Beneficiary::class)->find(1);
        $distributionBeneficiaryService->removeBeneficiaryInDistribution(1, $beneficiary);

        $distributionBeneficiary = $this->em->getRepository(DistributionBeneficiary::class)->findOneBy(['beneficiary' => 1, 'distributionData' => 1]);

        if(!$distributionBeneficiary){
            $this->assertTrue(1 == 1);
        }
    }*/
}
