<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\WingMoney;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Enum\ResidencyStatus;
use DateTime;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Entity\Commodity;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Enum\AssistanceType;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Enum\PhoneTypes;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use TransactionBundle\Entity\Transaction;
use UserBundle\Entity\User;
use function Matrix\trace;

class TestReportImportCommand extends KernelTestCase
{
    /** @var Application */
    private $application;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->application = new Application($kernel);

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    private function prepareTestAssistance(): Assistance
    {
        $oldPhones = $this->entityManager->getRepository(Phone::class)->findBy(['number' => '999999999']);

        foreach ($oldPhones as $oldPhone) {
            $this->entityManager->remove($oldPhone);
        }

        $oldTransactions = $this->entityManager->getRepository(Transaction::class)->findBy(['transactionId' => 'AMC6666666']);

        foreach ($oldTransactions as $oldTransaction) {
            $this->entityManager->remove($oldTransaction);
        }

        $assistance = new Assistance();
        $assistance->setName('Test wing money import');
        $assistance->setUpdatedOn(new DateTime());
        $assistance->setDateDistribution(new DateTime());
        $assistance->setArchived(false);
        $assistance->setValidated(true);
        $assistance->setCompleted(false);
        $assistance->setAssistanceType(AssistanceType::DISTRIBUTION);
        $assistance->setTargetType(AssistanceTargetType::INDIVIDUAL);

        $this->entityManager->persist($assistance);

        $commodity = new Commodity();
        $commodity->setAssistance($assistance);
        $commodity->setUnit('USD');
        $commodity->setValue(60.00);

        $this->entityManager->persist($commodity);

        $beneficiary = new Beneficiary();
        $beneficiary->setHead(true);
        $beneficiary->setResidencyStatus(ResidencyStatus::RESIDENT);

        $this->entityManager->persist($beneficiary);

        $phone = new Phone();
        $phone->setNumber('999999999');
        $phone->setType(PhoneTypes::MOBILE);
        $phone->setPrefix('+999');
        $phone->setProxy(false);
        $phone->setPerson($beneficiary->getPerson());

        $this->entityManager->persist($phone);

        $assistanceBeneficiary = new AssistanceBeneficiary();
        $assistanceBeneficiary->setAssistance($assistance);
        $assistanceBeneficiary->setBeneficiary($beneficiary);
        $assistanceBeneficiary->setRemoved(false);

        $this->entityManager->persist($assistanceBeneficiary);

        $assistance->getDistributionBeneficiaries()->add($assistanceBeneficiary);

        $this->entityManager->flush();
        $this->entityManager->clear();

        return $assistance;
    }

    public function testCommand()
    {
        $wingMoneyReportFilePath = __DIR__ . '/../../Resources/exampleWingMoneyReport.xlsx';

        $assistance = $this->prepareTestAssistance();

        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)->findOneBy([]);

        $wingMoneyImportCommand = $this->application->find('app:wing-money:import');
        $commandTester = new CommandTester($wingMoneyImportCommand);
        $commandTester->execute([
            'reportFile' => $wingMoneyReportFilePath,
            'assistance' => $assistance->getId(),
            'user' => $user->getId(),
            '--check' => false,
        ]);

        $totalTransactions = 0;

        $assistance = $this->entityManager->getRepository(Assistance::class)->find($assistance->getId());

        /** @var AssistanceBeneficiary $distributionBeneficiary */
        foreach ($assistance->getDistributionBeneficiaries() as $distributionBeneficiary) {
            $totalTransactions += $distributionBeneficiary->getTransactions()->count();

            if (!$distributionBeneficiary->getTransactions()->isEmpty()) {
                /** @var Transaction $transaction */
                $transaction = $distributionBeneficiary->getTransactions()->first();

                $this->assertEquals('USD 60.00', $transaction->getAmountSent());
                $this->assertEquals($user->getId(), $transaction->getSentBy()->getId());
                $this->assertEquals('AMC6666666', $transaction->getTransactionId());
            }
        }

        $this->assertEquals(1, $totalTransactions);
    }
}
