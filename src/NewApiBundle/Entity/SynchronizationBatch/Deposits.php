<?php
declare(strict_types=1);

namespace NewApiBundle\Entity\SynchronizationBatch;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\DBAL\SynchronizationBatchValidationTypeEnum;
use NewApiBundle\Entity\SynchronizationBatch;
use NewApiBundle\Enum\SynchronizationBatchValidationType;
use VoucherBundle\Entity\SmartcardDeposit;

/**
 * @ ORM\Entity
 * @ ORM\Table(name="synchronization_batch_deposit")
 */
class Deposits extends SynchronizationBatch
{
    /**
     * @var SmartcardDeposit[]
     */
    private $createdDeposits;

    public function __construct(array $requestData)
    {
        parent::__construct($requestData, SynchronizationBatchValidationType::DEPOSIT);
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
