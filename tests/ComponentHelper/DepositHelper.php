<?php

declare(strict_types=1);

namespace Tests\ComponentHelper;

use Component\Smartcard\Deposit\DepositFactory;
use Component\Smartcard\Deposit\Exception\DoubledDepositException;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Entity\SmartcardDeposit;
use Entity\User;
use InputType\Smartcard\DepositInputType;
use Psr\Cache\InvalidArgumentException;

/**
 * @property EntityManagerInterface $em
 */
trait DepositHelper
{
    /**
     * @param string $smartcardNumber
     * @param DepositInputType $depositInputType
     * @param User $user
     * @param DepositFactory $depositFactory
     * @return SmartcardDeposit
     * @throws DoubledDepositException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws InvalidArgumentException
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
