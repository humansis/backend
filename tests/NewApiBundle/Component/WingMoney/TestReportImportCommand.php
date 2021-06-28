<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\WingMoney;

use DistributionBundle\Entity\Assistance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use UserBundle\Entity\User;

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
        $wingMoneyReportFilePath = __DIR__ . '/../../Resources/MissingTranssactionToImport.xlsx';

        /** @var Assistance $assistance */
        $assistance = $this->entityManager->getRepository(Assistance::class)->findOneBy([]);

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
    }
}
