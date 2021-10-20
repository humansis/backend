<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\CreationMetadata;
use NewApiBundle\Entity\Helper\Source;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @ORM\MappedSuperclass()
 */
abstract class AbstractSynchronizationBatch
{
    use StandardizedPrimaryKey;
    use Source;
    use CreationMetadata;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="enum_synchronization_batch_state", nullable=false)
     */
    private $state;

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
    protected function __construct(array $requestData)
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
    public function setViolations(array $violations): void
    {
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

    /**
     * @param \DateTimeInterface|null $validatedAt
     */
    public function setValidatedAt(?\DateTimeInterface $validatedAt): void
    {
        $this->validatedAt = $validatedAt;
    }

}
