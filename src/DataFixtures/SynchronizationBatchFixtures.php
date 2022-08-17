<?php
declare(strict_types=1);

namespace DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Component\Smartcard\SmartcardDepositService;
use Entity\SynchronizationBatch\Deposits;

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
        $user = $this->getReference(UserFixtures::REF_VENDOR_SYR);

        foreach (self::DEPOSIT_SYNC_DATA as $syncData) {
            $sync = new Deposits($syncData);
            $sync->setCreatedBy($user);
            $manager->persist($sync);
        }
        $manager->flush();
        foreach (self::DEPOSIT_SYNC_DATA as $syncData) {
            $sync = new Deposits($syncData);
            $sync->setCreatedBy($user);
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
