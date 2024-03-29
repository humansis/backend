<?php

declare(strict_types=1);

namespace DataFixtures;

use Component\Smartcard\Messaging\Handler\SmartcardDepositMessageHandler;
use Component\Smartcard\Messaging\Message\SmartcardDepositMessage;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Entity\SynchronizationBatch\Deposits;
use Exception;
use InputType\SynchronizationBatch\CreateDepositInputType;

class SynchronizationBatchFixtures extends Fixture implements DependentFixtureInterface
{
    final public const DEPOSIT_SYNC_DATA = [
        ['{"reliefPackageId":1024, "createdAt": "2000-01-01T00:00:00+0200", "smartcardSerialNumber": "AAABBB123", "balanceBefore": null, "balanceAfter": 10.99}'],
        ['{"reliefPackageId":1, "createdAt": "2000-01-01T00:00:00+0200", "smartcardSerialNumber": "AAABBB123", "balanceBefore": 1000000.99, "balanceAfter": 10.99}'],
        ['{"reliefPackageId":null, "createdAt": "garbage", "smartcardSerialNumber": null, "balanceBefore": null, "balanceAfter": null}'],
        ['{"reliefPackageId":null}, {"somethingElse": "Dont know"}'],
    ];

    public function __construct(
        private readonly string $environment,
        private readonly SmartcardDepositMessageHandler $smartcardDepositMessageHandler
    ) {
    }

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

            $smartcardDepositMessage = new SmartcardDepositMessage(
                $user->getId(),
                CreateDepositInputType::class,
                $syncData['smartcardSerialNumber'] ?? null,
                $syncData
            );

            try {
                ($this->smartcardDepositMessageHandler)($smartcardDepositMessage);
            } catch (Exception) {
            }
        }

        $manager->flush();
        foreach (self::DEPOSIT_SYNC_DATA as $syncData) {
            $sync = new Deposits($syncData);
            $sync->setCreatedBy($user);
            $manager->persist($sync);
            $manager->flush();
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
