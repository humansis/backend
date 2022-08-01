<?php declare(strict_types=1);

namespace NewApiBundle\InputType\Smartcard;

use DateTime;
use DateTimeInterface;
use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SmartcardRegisterInputType implements InputTypeInterface
{
    /**
     * @Assert\NotNull()
     * @Assert\Type(type="string")
     *
     * @var string
     */
    private $serialNumber;

    /**
     * @Assert\NotNull()
     * @Assert\Type(type="int")
     *
     * @var int
     */
    private $beneficiaryId;

    /**
     * @Assert\DateTime()
     *
     * @var DateTimeInterface
     */
    private $createdAt;

    /**
     * @param string $serialNumber
     * @param int    $beneficiaryId
     * @param string $createdAt
     *
     * @return static
     */
    public static function create(string $serialNumber, int $beneficiaryId, string $createdAt): self
    {
        $self = new self();
        $self->setSerialNumber($serialNumber);
        $self->setBeneficiaryId($beneficiaryId);
        $self->setCreatedAt($createdAt);

        return $self;
    }

    /**
     * @return string
     */
    public function getSerialNumber(): string
    {
        return $this->serialNumber;
    }

    /**
     * @param string $serialNumber
     */
    public function setSerialNumber(string $serialNumber): void
    {
        $this->serialNumber = strtoupper($serialNumber);
    }

    /**
     * @return int
     */
    public function getBeneficiaryId(): int
    {
        return $this->beneficiaryId;
    }

    /**
     * @param int $beneficiaryId
     */
    public function setBeneficiaryId(int $beneficiaryId): void
    {
        $this->beneficiaryId = $beneficiaryId;
    }

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param string $createdAt
     */
    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = DateTime::createFromFormat('Y-m-d\TH:i:sO', $createdAt);
    }

}
