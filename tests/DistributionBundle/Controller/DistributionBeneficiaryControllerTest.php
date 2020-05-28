<?php


namespace Tests\DistributionBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use CommonBundle\Entity\Adm4;
use CommonBundle\Entity\Location;
use DistributionBundle\Entity\Commodity;
use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Entity\ModalityType;
use DistributionBundle\Entity\SelectionCriteria;
use DistributionBundle\Utils\DistributionCSVService;
use DistributionBundle\Utils\DistributionService;
use ProjectBundle\Entity\Project;
use Symfony\Component\BrowserKit\Client;
use Tests\BeneficiaryBundle\Controller\HouseholdControllerTest;
use Tests\BMSServiceTestCase;

class DistributionBeneficiaryControllerTest extends BMSServiceTestCase
{
    /**
     * @throws \Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("jms_serializer");
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');
    }

    /**
     * @throws \Exception
     */
    public function testCreateDistributionBeneficiary()
    {
        //We check if there is an user in the Beneficiary to use him for the test :
        $beneficiary = $this->em->getRepository(Beneficiary::class)->findAll();

        // If there is no user, we display an error :
        if (!$beneficiary) {
            print_r("\nThere is no beneficiary with the ID specified to execute the test.\n");
            $this->markTestIncomplete("There is no beneficiary with the ID specified to execute the test.");
        }

        $distributionData = $this->em->getRepository(DistributionData::class)->findAll();

        if (!$distributionData) {
            print_r("\nThere is no distribution with the ID specified to execute the test.\n");
            $this->markTestIncomplete("There is no distribution with the ID specified to execute the test.");
        }

        // If everything is ok, we create a new distributionBeneficiary
        $distributionBeneficiary = new DistributionBeneficiary();
        $distributionBeneficiary->setBeneficiary($beneficiary[0])
            ->setDistributionData($distributionData[0])
            ->setRemoved(0);

        $this->em->persist($distributionBeneficiary);

        $this->em->flush();

        $distributionBeneficiary = $this->em->getRepository(DistributionBeneficiary::class)->find($distributionBeneficiary->getId());

        if (!$distributionBeneficiary) {
            print_r("\nThere was an error while creating the new distributionBeneficiary during the test.\n");
            $this->markTestIncomplete("There was an error while creating the new distributionBeneficiary during the test.");
        }

        try {
            $this->assertTrue($distributionBeneficiary instanceof DistributionBeneficiary);
        } catch (\Exception $exception) {
            $this->em->remove($distributionBeneficiary);
            $this->em->flush();

            $this->fail("\nThe mapping of fields of DistributionBeneficiary entity is not correct (1).\n");
            return false;
        }

        return $distributionBeneficiary;
    }

    /**
     * @param DistributionBeneficiary $distributionBeneficiary
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @depends testCreateDistributionBeneficiary
     */
    public function testRemoveDistributionBeneficiary(DistributionBeneficiary $distributionBeneficiary)
    {
        $beneficiaryId = $distributionBeneficiary->getBeneficiary()->getId();
        $distributionId = $distributionBeneficiary->getDistributionData()->getId();

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);
        $body = array(
            'justification' => 'Jusitification for deletion'
        );

        $crawler = $this->request('POST', '/api/wsse/distributions/'. $distributionId .'/beneficiaries/'. $beneficiaryId .'/remove', $body);
        
        $listDistributionBeneficiary = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        return true;
    }
}
