<?php

declare(strict_types=1);

namespace Tests\ComponentHelper;

use Component\Smartcard\Deposit\DepositFactory;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Entity\SmartcardDeposit;
use Entity\User;
use Exception;
use InputType\Smartcard\DepositInputType;
use Symfony\Component\DependencyInjection\Container;

/**
 * @property Container $container
 * @property EntityManagerInterface $em
 */
trait DepositHelper
{
    /**
     * @throws Exception
     */
    public function createDeposit(
        string $smartcardNumber,
        DepositInputType $depositInputType,
        User $user,
        DepositFactory $depositFactory,
    ): SmartcardDeposit {
        return $depositFactory->create($smartcardNumber, $depositInputType, $user);
    }

    public static function buildDepositInputType(int $reliefPackageId, float $value): DepositInputType
    {
        $depositInputType = new DepositInputType();
        $depositInputType->setCreatedAt((new DateTime()));
        $depositInputType->setReliefPackageId($reliefPackageId);
        $depositInputType->setValue($value);

        return $depositInputType;
    }
}
