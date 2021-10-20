<?php
declare(strict_types=1);

namespace NewApiBundle\Entity\SynchronizationBatch;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractSynchronizationBatch;
use VoucherBundle\Entity\SmartcardPurchase;

/**
 * @ORM\Entity
 * @ORM\Table(name="synchronization_batch_purchase")
 */
class Purchases extends AbstractSynchronizationBatch
{
    /**
     * @var SmartcardPurchase[]
     */
    private $createdPurchases;

    public function __construct(array $requestData)
    {
        parent::__construct($requestData);
        $this->createdPurchases = new ArrayCollection();
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

    /**
     * @param SmartcardPurchase $createdDeposit
     */
    public function addCreatedDeposit(SmartcardPurchase $createdDeposit): void
    {
        $this->createdPurchases[] = $createdDeposit;
    }
}
