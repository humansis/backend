<?php

namespace Tests\Controller\OfflineApp;

use DateTime;
use DateTimeInterface;
use Entity\Beneficiary;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Component\Smartcard\Deposit\DepositFactory;
use Entity\Assistance\ReliefPackage;
use Enum\ModalityType;
use InputType\Smartcard\DepositInputType;
use Symfony\Component\HttpFoundation\Response;
use Tests\BMSServiceTestCase;
use Entity\SmartcardBeneficiary;
use Entity\SmartcardDeposit;
use Enum\SmartcardStates;

class SmartcardDepositControllerTest extends BMSServiceTestCase
{
    public function setUp(): void
    {
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::getContainer()->get('test.client');
    }

    protected function tearDown(): void
    {
        $this->removeSmartcards('1234ABC');

        parent::tearDown();
    }

    private function removeSmartcards(string $serialNumber): void
    {
        $smartcardBeneficiaries = $this->em->getRepository(SmartcardBeneficiary::class)->findBy(
            ['serialNumber' => $serialNumber],
            ['id' => 'asc']
        );
        foreach ($smartcardBeneficiaries as $smartcardBeneficiary) {
            $this->em->remove($smartcardBeneficiary);
        }
        $this->em->flush();
    }

    public function testDepositToSmartcardV5(): void
    {
        $ab = $this->assistanceBeneficiaryWithoutRelief();
        $bnf = $ab->getBeneficiary();
        $smartcardBeneficiary = $this->getSmartcardForBeneficiary('1234ABC', $bnf);
        $reliefPackage = $this->createReliefPackage($ab);
        $uniqueDate = $this->getUnusedDepositDate()->format(DateTimeInterface::ATOM);

        $this->request('POST', '/api/basic/offline-app/v5/smartcards/' . $smartcardBeneficiary->getSerialNumber() . '/deposit', [
            'reliefPackageId' => $reliefPackage->getId(),
            'value' => 255.25,
            'balance' => 300.00,
            'createdAt' => $uniqueDate,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testDoubledDeposit(): void
    {
        $ab = $this->assistanceBeneficiaryWithoutRelief();
        $bnf = $ab->getBeneficiary();
        $smartcardBeneficiary = $this->getSmartcardForBeneficiary('1234ABC', $bnf);
        $reliefPackage = $this->createReliefPackage($ab);
        $date = $this->getUnusedDepositDate();
        $depositFactory = self::getContainer()->get(DepositFactory::class);
        $depositCreateInputFile = DepositInputType::create($reliefPackage->getId(), 255.25, 300.00, $date);
        $depositFactory->create('1234ABC', $depositCreateInputFile, $this->getTestUser());

        $this->request('POST', '/api/basic/offline-app/v5/smartcards/' . $smartcardBeneficiary->getSerialNumber() . '/deposit', [
            'reliefPackageId' => $reliefPackage->getId(),
            'value' => 255.25,
            'balance' => 300.00,
            'createdAt' => $date->format(DateTimeInterface::ATOM),
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertEquals(Response::HTTP_ACCEPTED, $this->client->getResponse()->getStatusCode());

        if (!$this->em->isOpen()) {     // this is expected, because checking of doubled hash closes Entity Manager
            $this->em = $this->em::create(
                $this->em->getConnection(),
                $this->em->getConfiguration()
            );
        }
    }

    private function getUnusedDepositDate(): DateTime
    {
        $date = new DateTime();
        do {
            $date = $date->modify('-1 second');
            $deposit = $this->em->getRepository(SmartcardDeposit::class)->findOneBy(['distributedAt' => $date]);
        } while ($deposit != null);

        return $date;
    }

    private function someSmartcardAssistance(): ?Assistance
    {
        foreach ($this->em->getRepository(Assistance::class)->findBy([], ['id' => 'asc']) as $assistance) {
            foreach ($assistance->getCommodities() as $commodity) {
                if ($commodity->getModalityType() === ModalityType::SMART_CARD) {
                    return $assistance;
                }
            }
        }

        return null;
    }

    private function assistanceBeneficiaryWithoutRelief(): AssistanceBeneficiary
    {
        /** @var Assistance $assistance */
        foreach ($this->em->getRepository(Assistance::class)->findBy([], ['id' => 'asc']) as $assistance) {
            foreach ($assistance->getCommodities() as $commodity) {
                if (ModalityType::SMART_CARD !== $commodity->getModalityType()) {
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
        $assistanceBeneficiary->setBeneficiary(
            $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc'])
        );

        $this->em->persist($assistanceBeneficiary);

        return $assistanceBeneficiary;
    }

    private function getSmartcardForBeneficiary(string $serialNumber, Beneficiary $beneficiary): SmartcardBeneficiary
    {
        /** @var SmartcardBeneficiary[] $smartcards */
        $smartcardBeneficiaries = $this->em->getRepository(SmartcardBeneficiary::class)->findBy(
            ['serialNumber' => $serialNumber],
            ['id' => 'asc']
        );

        foreach ($smartcardBeneficiaries as $smartcardBeneficiary) {
            if ($smartcardBeneficiary->getState() === SmartcardStates::ACTIVE) {
                $smartcardBeneficiary->setBeneficiary($beneficiary);

                return $smartcardBeneficiary;
            }
        }

        $smartcardBeneficiary = new SmartcardBeneficiary($serialNumber, new DateTime('now'));
        $smartcardBeneficiary->setBeneficiary($beneficiary);
        $smartcardBeneficiary->setState(SmartcardStates::ACTIVE);

        $this->em->persist($smartcardBeneficiary);
        $this->em->flush();

        return $smartcardBeneficiary;
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
