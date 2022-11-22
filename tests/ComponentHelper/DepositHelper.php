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
     * @param string $smartcardNumber
     * @param DepositInputType $depositInputType
     * @param User $user
     * @return SmartcardDeposit
     * @throws Exception
     */
    public function createDeposit(
        string $smartcardNumber,
        DepositInputType $depositInputType,
        User $user
    ): SmartcardDeposit {
        return self::$container->get(DepositFactory::class)->create($smartcardNumber, $depositInputType, $user);
    }

    /**
     * @param int $reliefPackageId
     * @param float $value
     * @return DepositInputType
     */
    public static function buildDepositInputType(int $reliefPackageId, float $value): DepositInputType
    {
        $depositInputType = new DepositInputType();
        $depositInputType->setCreatedAt((new DateTime())->format(DateTimeInterface::ATOM));
        $depositInputType->setReliefPackageId($reliefPackageId);
        $depositInputType->setValue($value);

        return $depositInputType;
    }
}
