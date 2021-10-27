<?php

declare(strict_types=1);

namespace NewApiBundle\InputType\SynchronizationBatch;

use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"CreateDepositInputType", "Strict"})
 */
class CreateDepositInputType implements InputTypeInterface
{
    private $reliefPackageId;
    private $createdAt;
    private $smartcardSerialNumber;
    private $balanceBefore;
    private $balanceAfter;
}
