<?php

declare(strict_types=1);

namespace Entity\SynchronizationBatch;

use Doctrine\ORM\Mapping as ORM;
use Entity\SynchronizationBatch;
use Entity\SmartcardPurchase;

#[ORM\Entity(repositoryClass: '\Repository\SynchronizationBatchRepository')]
class Purchases extends SynchronizationBatch
{
    /**
     * @var SmartcardPurchase[]
     */
    private array $createdPurchases;

    public function __construct(array $requestData)
    {
        parent::__construct($requestData);
        $this->createdPurchases = [];
    }

    /**
     * @return SmartcardPurchase[]
     */
    public function getCreatedPurchases(): array
    {
        return $this->createdPurchases;
    }

    /**
     * @param SmartcardPurchase[] $createdPurchases
     */
    public function setCreatedPurchases(array $createdPurchases): void
    {
        $this->createdPurchases = $createdPurchases;
    }

    public function addCreatedDeposit(SmartcardPurchase $createdDeposit): void
    {
        $this->createdPurchases[] = $createdDeposit;
    }
}
