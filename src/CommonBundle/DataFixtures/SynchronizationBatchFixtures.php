<?php
declare(strict_types=1);

namespace CommonBundle\DataFixtures;

use BeneficiaryBundle\Entity\Beneficiary;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Smartcard\SmartcardDepositService;
use NewApiBundle\Entity\SynchronizationBatch\Deposits;
use TransactionBundle\Entity\Transaction;
use TransactionBundle\Utils\Provider\KHMFinancialProvider;

class SynchronizationBatchFixtures extends Fixture implements DependentFixtureInterface
{
    /** @var string */
    private $environment;

    /** @var SmartcardDepositService */
    private $smartcardDepositService;

    const DEPOSIT_SYNC_DATA = [
        ['{"reliefPackageId":1024, "createdAt": "2000-01-01", "smartcardSerialNumber": "AAABBB123", "balanceBefore": null, "balanceAfter": 10.99}'],
        ['{"reliefPackageId":1, "createdAt": "2000-01-01", "smartcardSerialNumber": "AAABBB123", "balanceBefore": 1000000.99, "balanceAfter": 10.99}'],
        ['{"reliefPackageId":null, "createdAt": "garbage", "smartcardSerialNumber": null, "balanceBefore": null, "balanceAfter": null}'],
        ['{"reliefPackageId":null}, {"somethingElse": "Dont know"}'],
    ];

    /**
     * @param string                  $environment
     * @param SmartcardDepositService $smartcardDepositService
     */
    public function __construct(string $environment, SmartcardDepositService $smartcardDepositService)
    {
        $this->environment = $environment;
        $this->smartcardDepositService = $smartcardDepositService;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        if ('prod' === $this->environment) {
            // this fixtures are not for production environment
            return;
        }

        foreach (self::DEPOSIT_SYNC_DATA as $syncData) {
            $sync = new Deposits($syncData);
            $manager->persist($sync);
        }
        $manager->flush();
        foreach (self::DEPOSIT_SYNC_DATA as $syncData) {
            $sync = new Deposits($syncData);
            $manager->persist($sync);
            $manager->flush();

            $this->smartcardDepositService->validateSync($sync);
        }
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class,
            SmartcardFixtures::class,
        ];
    }
}
