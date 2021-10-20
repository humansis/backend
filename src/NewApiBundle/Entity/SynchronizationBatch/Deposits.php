<?php
declare(strict_types=1);

namespace NewApiBundle\Entity\SynchronizationBatch;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractSynchronizationBatch;
use VoucherBundle\Entity\SmartcardDeposit;

/**
 * @ORM\Entity
 * @ORM\Table(name="synchronization_batch_deposit")
 */
class Deposits extends AbstractSynchronizationBatch
{
    /**
     * @var SmartcardDeposit[]
     */
    private $createdDeposits;

    public function __construct(array $requestData)
    {
        parent::__construct($requestData);
        $this->createdDeposits = new ArrayCollection();
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

    /**
     * @param SmartcardDeposit $createdDeposit
     */
    public function addCreatedDeposit(SmartcardDeposit $createdDeposit): void
    {
        $this->createdDeposits[] = $createdDeposit;
    }

}
