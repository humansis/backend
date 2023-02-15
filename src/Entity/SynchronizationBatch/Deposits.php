<?php

declare(strict_types=1);

namespace Entity\SynchronizationBatch;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Entity\SynchronizationBatch;
use Entity\SmartcardDeposit;

#[ORM\Entity(repositoryClass: '\Repository\SynchronizationBatchRepository')]
class Deposits extends SynchronizationBatch
{
    /**
     * @var Collection| SmartcardDeposit[]
     */
    private Collection|array $createdDeposits;

    public function __construct(array $requestData)
    {
        parent::__construct($requestData);
        $this->createdDeposits = [];
    }

    /**
     * @return SmartcardDeposit[]
     */
    public function getCreatedDeposits(): array
    {
        return $this->createdDeposits;
    }

    /**
     * @param SmartcardDeposit[] $createdDeposits
     */
    public function setCreatedDeposits(array $createdDeposits): void
    {
        $this->createdDeposits = $createdDeposits;
    }

    public function addCreatedDeposit(SmartcardDeposit $createdDeposit): void
    {
        $this->createdDeposits[] = $createdDeposit;
    }
}
