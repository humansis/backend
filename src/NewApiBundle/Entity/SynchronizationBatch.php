<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\CreationMetadata;
use NewApiBundle\Entity\Helper\Source;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use NewApiBundle\Enum\SynchronizationBatchState;
use NewApiBundle\Enum\SynchronizationBatchValidationType;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @ORM\Entity
 * @ ORM\InheritanceType(value="SINGLE_TABLE")
 * @ ORM\DiscriminatorColumn(name="validation_type", type="enum_synchronization_batch_validation_type")
 * @ ORM\DiscriminatorMap({
 *     "Deposits"="\NewApiBundle\Entity\SynchronizationBatch\Deposits",
 *     "Purchases"="\NewApiBundle\Entity\SynchronizationBatch\Purchases"
 * })
 */
class SynchronizationBatch
{
    use StandardizedPrimaryKey;
    use Source;
    use CreationMetadata;

    /**
     * @var string
     *
     * @ORM\Column(name="validation_type", type="enum_synchronization_batch_validation_type", nullable=false)
     */
    private $validationType = SynchronizationBatchValidationType::DEPOSIT;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="enum_synchronization_batch_state", nullable=false)
     */
    private $state = SynchronizationBatchState::UPLOADED;

    /**
     * @var array
     *
     * @ORM\Column(name="request_data", type="json", nullable=false)
     */
    private $requestData;

    /**
     * @var array<ConstraintViolationListInterface>
     *
     * @ORM\Column(name="violations", type="json", nullable=true)
     */
    private $violations;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="validated_at", type="datetime", nullable=true)
     */
    private $validatedAt;

    /**
     * @param array $requestData
     */
    public function __construct(array $requestData)
    {
        $this->requestData = $requestData;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @return array
     */
    public function getRequestData(): array
    {
        return $this->requestData;
    }

    /**
     * @return ConstraintViolationListInterface[]
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

    /**
     * @param ConstraintViolationListInterface[] $violations
     */
    public function setViolations(array $violations, ?\DateTimeInterface $validatedAt = null): void
    {
        if ($this->state !== SynchronizationBatchState::UPLOADED) {
            throw new \InvalidArgumentException("Violation shouldn't be added to processed batches");
        }
        $this->validatedAt = $validatedAt ?? new \DateTimeImmutable();
        $this->violations = $violations;
    }

    public function addViolation($index, ConstraintViolationListInterface $violations): void
    {
        $this->violations[$index] = $violations;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getValidatedAt(): ?\DateTimeInterface
    {
        return $this->validatedAt;
    }

}
