<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller\OfflineApp;

use BeneficiaryBundle\Entity\Beneficiary;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\ReliefPackage;
use NewApiBundle\Enum\ModalityType;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Enum\SmartcardStates;

class SmartcardDepositControllerTest extends AbstractFunctionalApiTest
{
    protected function tearDown()
    {
        $this->removeSmartcards('1234ABC');

        parent::tearDown();
    }

    private function removeSmartcards(string $serialNumber): void
    {
        $smartcards = $em->getRepository(Smartcard::class)->findBy(['serialNumber' => $serialNumber], ['id' => 'asc']);
        foreach ($smartcards as $smartcard) {
            $em->remove($smartcard);
        }
        $em->flush();
    }

    public function testDepositToSmartcard()
    {
        $ab = $this->assistanceBeneficiaryWithoutRelief();
        $bnf = $ab->getBeneficiary();
        $smartcard = $this->getSmartcardForBeneficiary('1234ABC', $bnf);

        $reliefPackage = $this->createReliefPackage($ab);

        $this->client->request('POST', '/api/wsse/offline-app/v4/smartcards/'.$smartcard->getSerialNumber().'/deposit', [
            'assistanceId' => $ab->getAssistance()->getId(),
            'value' => 255.25,
            'balanceBefore' => 260.00,
            'balanceAfter' => 300.00,
            'createdAt' => '2020-02-02T12:00:00Z',
            'beneficiaryId' => $bnf->getId(),
        ], [], $this->addAuth());

        $smartcard = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('id', $smartcard);
        $this->assertArrayHasKey('serialNumber', $smartcard);
        $this->assertArrayHasKey('state', $smartcard);
        $this->assertArrayHasKey('currency', $smartcard);
        $this->assertArrayHasKey('createdAt', $smartcard);
    }

    private function someSmartcardAssistance(): ?Assistance
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        foreach ($em->getRepository(Assistance::class)->findBy([], ['id'=>'asc']) as $assistance) {
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
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        /** @var Assistance $assistance */
        foreach ($em->getRepository(Assistance::class)->findBy([], ['id'=>'asc']) as $assistance) {
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
        $assistanceBeneficiary->setBeneficiary($em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc']));

        $em->persist($assistanceBeneficiary);

        return $assistanceBeneficiary;
    }

    private function getSmartcardForBeneficiary(string $serialNumber, Beneficiary $beneficiary): Smartcard
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        /** @var Smartcard[] $smartcards */
        $smartcards = $em->getRepository(Smartcard::class)->findBy(['serialNumber' => $serialNumber], ['id' => 'asc']);

        foreach ($smartcards as $smartcard) {
            if ($smartcard->getState() === SmartcardStates::ACTIVE) {
                $smartcard->setBeneficiary($beneficiary);

                return $smartcard;
            }
        }

        $smartcard = new Smartcard($serialNumber, new \DateTime('now'));
        $smartcard->setBeneficiary($beneficiary);
        $smartcard->setState(SmartcardStates::ACTIVE);

        $em->persist($smartcard);
        $em->flush();

        return $smartcard;
    }

    private function createReliefPackage(AssistanceBeneficiary $ab): ReliefPackage
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        $reliefPackage = new ReliefPackage(
            $ab,
            ModalityType::SMART_CARD,
            $ab->getAssistance()->getCommodities()[0]->getValue(),
            $ab->getAssistance()->getCommodities()[0]->getUnit(),
        );

        $em->persist($reliefPackage);
        $em->flush();

        return $reliefPackage;
    }
}
