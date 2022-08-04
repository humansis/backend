<?php


namespace Tests\DistributionBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use Tests\BMSServiceTestCase;

class AssistanceBeneficiaryControllerTest extends BMSServiceTestCase
{
    /**
     * @throws \Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("serializer");
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    /**
     * @throws \Exception
     */
    public function testCreateAssistanceBeneficiary()
    {
        //We check if there is an user in the Beneficiary to use him for the test :
        $beneficiary = $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'desc']);

        // If there is no user, we display an error :
        if (!$beneficiary) {
            print_r("\nThere is no beneficiary with the ID specified to execute the test.\n");
            $this->markTestIncomplete("There is no beneficiary with the ID specified to execute the test.");
        }

        /** @var Assistance $assistance */
        $assistance = $this->em->getRepository(Assistance::class)->findOneBy([
            'validatedBy' => null,
            'completed' => false,
            'archived' => false,
        ], ['id' => 'desc']);

        if (!$assistance) {
            print_r("\nThere is no distribution with the ID specified to execute the test.\n");
            $this->markTestIncomplete("There is no distribution with the ID specified to execute the test.");
        }

        $alreadyExistingBeneficiary = $this->em->getRepository(AssistanceBeneficiary::class)
            ->findOneBy(['beneficiary' => $beneficiary, 'assistance' => $assistance], ['id' => 'asc']);
        if ($alreadyExistingBeneficiary) {
            print_r("\nThere already is beneficiary ID specified to execute the test in assistance.\n");
            $this->markTestIncomplete("There already is beneficiary ID specified to execute the test in assistance.");
        }

        // If everything is ok, we create a new assistanceBeneficiary
        $assistanceBeneficiary = new AssistanceBeneficiary();
        $assistanceBeneficiary->setBeneficiary($beneficiary)
            ->setAssistance($assistance)
            ->setRemoved(0);

        $this->em->persist($assistanceBeneficiary);

        $this->em->flush();

        $assistanceBeneficiary = $this->em->getRepository(AssistanceBeneficiary::class)->find($assistanceBeneficiary->getId());

        if (!$assistanceBeneficiary) {
            print_r("\nThere was an error while creating the new assistanceBeneficiary during the test.\n");
            $this->markTestIncomplete("There was an error while creating the new assistanceBeneficiary during the test.");
        }

        try {
            $this->assertTrue($assistanceBeneficiary instanceof AssistanceBeneficiary);
        } catch (\Exception $exception) {
            $this->em->remove($assistanceBeneficiary);
            $this->em->flush();

            $this->fail("\nThe mapping of fields of AssistanceBeneficiary entity is not correct (1).\n");
            return false;
        }

        return $assistanceBeneficiary;
    }

    /**
     * @param AssistanceBeneficiary $assistanceBeneficiary
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @depends testCreateAssistanceBeneficiary
     */
    public function testRemoveAssistanceBeneficiary(AssistanceBeneficiary $assistanceBeneficiary)
    {
        $beneficiaryId = $assistanceBeneficiary->getBeneficiary()->getId();
        $distributionId = $assistanceBeneficiary->getAssistance()->getId();

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);
        $body = array(
            'justification' => 'Jusitification for deletion'
        );

        $crawler = $this->request('POST', '/api/wsse/distributions/'. $distributionId .'/beneficiaries/'. $beneficiaryId .'/remove', $body);
        
        $listAssistanceBeneficiary = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        return true;
    }
}
