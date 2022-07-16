<?php

namespace Tests\NewApiBundle\Controller\OfflineApp;

use NewApiBundle\Entity\Beneficiary;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use NewApiBundle\Component\Smartcard\Deposit\DepositFactory;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Enum\ModalityType;
use NewApiBundle\InputType\Smartcard\DepositInputType;
use Tests\BMSServiceTestCase;
use NewApiBundle\Entity\User;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\Enum\SmartcardStates;

class SmartcardDepositControllerTest extends BMSServiceTestCase
{
    public function setUp()
    {
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);
    }

    protected function tearDown()
    {
        $this->removeSmartcards('1234ABC');

        parent::tearDown();
    }

    private function removeSmartcards(string $serialNumber): void
    {
        $smartcards = $this->em->getRepository(Smartcard::class)->findBy(['serialNumber' => $serialNumber], ['id' => 'asc']);
        foreach ($smartcards as $smartcard) {
            $this->em->remove($smartcard);
        }
        $this->em->flush();
    }

    public function testDepositToSmartcard()
    {
        $ab = $this->assistanceBeneficiaryWithoutRelief();
        $bnf = $ab->getBeneficiary();
        $smartcard = $this->getSmartcardForBeneficiary('1234ABC', $bnf);

        $reliefPackage = $this->createReliefPackage($ab);

        $this->request('POST', '/api/wsse/offline-app/v4/smartcards/'.$smartcard->getSerialNumber().'/deposit', [
            'assistanceId' => $ab->getAssistance()->getId(),
            'value' => 255.25,
            'balanceBefore' => 260.00,
            'balanceAfter' => 300.00,
            'createdAt' => '2020-02-02T12:00:00Z',
            'beneficiaryId' => $bnf->getId(),
        ]);

        $smartcard = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('id', $smartcard);
        $this->assertArrayHasKey('serialNumber', $smartcard);
        $this->assertArrayHasKey('state', $smartcard);
        $this->assertArrayHasKey('currency', $smartcard);
        $this->assertArrayHasKey('createdAt', $smartcard);
    }

    public function testDepositToSmartcardV5(): void
    {
        $ab = $this->assistanceBeneficiaryWithoutRelief();
        $bnf = $ab->getBeneficiary();
        $smartcard = $this->getSmartcardForBeneficiary('1234ABC', $bnf);
        $reliefPackage = $this->createReliefPackage($ab);
        $uniqueDate = $this->getUnusedDepositDate()->format(\DateTimeInterface::ATOM);

        $this->request('POST', '/api/wsse/offline-app/v5/smartcards/'.$smartcard->getSerialNumber().'/deposit', [
            'reliefPackageId' => $reliefPackage->getId(),
            'value' => 255.25,
            'balance' => 300.00,
            'createdAt' => $uniqueDate,
        ]);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testDoubledDeposit(): void
    {
        $ab = $this->assistanceBeneficiaryWithoutRelief();
        $bnf = $ab->getBeneficiary();
        $smartcard = $this->getSmartcardForBeneficiary('1234ABC', $bnf);
        $reliefPackage = $this->createReliefPackage($ab);
        $date = $this->getUnusedDepositDate();
        $depositFactory = self::$container->get(DepositFactory::class);
        $depositCreateInputFile = DepositInputType::create($reliefPackage->getId(), 255.25, 300.00, $date);
        $depositFactory->create('1234ABC', $depositCreateInputFile, $this->getTestUser(self::USER_TESTER));

        $this->request('POST', '/api/wsse/offline-app/v5/smartcards/'.$smartcard->getSerialNumber().'/deposit', [
            'reliefPackageId' => $reliefPackage->getId(),
            'value' => 255.25,
            'balance' => 300.00,
            'createdAt' => $date->format(\DateTimeInterface::ATOM),
        ]);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertEquals(202, $this->client->getResponse()->getStatusCode());
    }

    private function getUnusedDepositDate(): \DateTimeImmutable
    {
        $date = new \DateTimeImmutable();
        do {
            $date = $date->modify('-1 second');
            $deposit = $this->em->getRepository(SmartcardDeposit::class)->findOneBy(['distributedAt' => $date]);
        } while ($deposit != null);

        return $date;
    }

    private function someSmartcardAssistance(): ?Assistance
    {
        foreach ($this->em->getRepository(Assistance::class)->findBy([], ['id'=>'asc']) as $assistance) {
            foreach ($assistance->getCommodities() as $commodity) {
                if ('Smartcard' === $commodity->getModalityType()->getName()) {
                    return $assistance;
                }
            }
        }

        return null;
    }

    private function assistanceBeneficiaryWithoutRelief(): AssistanceBeneficiary
    {
        /** @var Assistance $assistance */
        foreach ($this->em->getRepository(Assistance::class)->findBy([], ['id'=>'asc']) as $assistance) {
            foreach ($assistance->getCommodities() as $commodity) {
                if (ModalityType::SMART_CARD !== $commodity->getModalityType()->getName()) {
                    continue 2;
                }
            }

            foreach ($assistance->getDistributionBeneficiaries() as $assistanceBeneficiary) {
                if ($assistanceBeneficiary->getReliefPackages()->isEmpty()) {
                    return $assistanceBeneficiary;
                }
            }
        }

        $assistanceBeneficiary = new AssistanceBeneficiary();
        $assistanceBeneficiary->setAssistance($this->someSmartcardAssistance());
        $assistanceBeneficiary->setBeneficiary($this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc']));

        $this->em->persist($assistanceBeneficiary);

        return $assistanceBeneficiary;
    }

    private function getSmartcardForBeneficiary(string $serialNumber, Beneficiary $beneficiary): Smartcard
    {
        /** @var Smartcard[] $smartcards */
        $smartcards = $this->em->getRepository(Smartcard::class)->findBy(['serialNumber' => $serialNumber], ['id' => 'asc']);

        foreach ($smartcards as $smartcard) {
            if ($smartcard->getState() === SmartcardStates::ACTIVE) {
                $smartcard->setBeneficiary($beneficiary);

                return $smartcard;
            }
        }

        $smartcard = new Smartcard($serialNumber, new \DateTime('now'));
        $smartcard->setBeneficiary($beneficiary);
        $smartcard->setState(SmartcardStates::ACTIVE);

        $this->em->persist($smartcard);
        $this->em->flush();

        return $smartcard;
    }

    private function createReliefPackage(AssistanceBeneficiary $ab): ReliefPackage
    {
        $reliefPackage = new ReliefPackage(
            $ab,
            ModalityType::SMART_CARD,
            $ab->getAssistance()->getCommodities()[0]->getValue(),
            $ab->getAssistance()->getCommodities()[0]->getUnit(),
        );

        $this->em->persist($reliefPackage);
        $this->em->flush();

        return $reliefPackage;
    }
}
