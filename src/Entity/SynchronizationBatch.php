<?php
declare(strict_types=1);

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\CreatedAt;
use Entity\Helper\CreatedBy;
use Entity\Helper\Source;
use Entity\Helper\StandardizedPrimaryKey;
use Enum\SynchronizationBatchState;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @ORM\Entity(repositoryClass="\Repository\SynchronizationBatchRepository")
 * @ORM\InheritanceType(value="SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="validationType", type="string")
 * @ORM\DiscriminatorMap({
 *     "Deposits"="\Entity\SynchronizationBatch\Deposits",
 *     "Purchases"="\Entity\SynchronizationBatch\Purchases"
 * })
 * @ORM\HasLifecycleCallbacks
 */
abstract class SynchronizationBatch
{
    use StandardizedPrimaryKey;
    use Source;
    use CreatedAt;
    use CreatedBy;

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
     * @var string serialized ConstraintViolationListInterface[]
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
     * @param array  $requestData
     */
    protected function __construct(array $requestData)
    {
        $this->requestData = $requestData;
    }

    /**
     * @param string $state
     */
    public function setState(string $state): void
    {
        if (!in_array($state, SynchronizationBatchState::values())) {
            throw new \InvalidArgumentException("Invalid ".get_class($this)." state: ".$state);
        }
        $this->state = $state;
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
     * @return array[]|null
     */
    public function getViolations(): ?array
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
        $this->violations = [];
        foreach ($violations as $rowKey => $violationList) {
            $this->violations[$rowKey] = $violationList ? $this->serializeViolations($violationList) : null;
        }
    }

    private function serializeViolations(ConstraintViolationListInterface $violationList): array
    {
        $data = [];
        foreach ($violationList as $rowKey => $subViolation) {
            if ($subViolation instanceof ConstraintViolationListInterface) {
                $data[$rowKey] = $this->serializeViolations($subViolation);
            }
            if ($subViolation instanceof ConstraintViolationInterface) {
                $data[$subViolation->getPropertyPath()][] = $subViolation->getMessage();
            }
        }
        return $data;
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
