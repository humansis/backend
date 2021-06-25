<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\WingMoney;

use DistributionBundle\Entity\Assistance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

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

    public function testCommand()
    {
        $wingMoneyReportFilePath = __DIR__ . '/../../Resources/exampleWingMoneyReport.xlsx';

        /** @var Assistance $assistance */
        $assistance = $this->entityManager->getRepository(Assistance::class)->findOneBy([]);

        $wingMoneyImportCommand = $this->application->find('app:wing-money:import');
        $commandTester = new CommandTester($wingMoneyImportCommand);
        $commandTester->execute([
            'reportFile' => $wingMoneyReportFilePath,
            'assistance' => $assistance->getId(),
        ]);
    }
}
