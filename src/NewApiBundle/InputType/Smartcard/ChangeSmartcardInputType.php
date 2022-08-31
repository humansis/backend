<?php declare(strict_types=1);

namespace NewApiBundle\InputType\Smartcard;

use DateTime;
use DateTimeInterface;
use NewApiBundle\Request\InputTypeInterface;
use NewApiBundle\Validator\Constraints\Enum;
use Symfony\Component\Validator\Constraints as Assert;

class ChangeSmartcardInputType implements InputTypeInterface
{
    /**
     * @Enum(enumClass="VoucherBundle\Enum\SmartcardStates")
     * @var string
     */
    private $state;

    /**
     * @Assert\DateTime()
     * @var DateTimeInterface
     */
    private $createdAt;

    /**
     * @var bool
     */
    private $suspicious = false;

    /**
     * @var string|null
     */
    private $suspiciousReason;

    /**
     * @return bool
     */
    public function isSuspicious(): bool
    {
        return $this->suspicious;
    }

    /**
     * @param bool $suspicious
     */
    public function setSuspicious(bool $suspicious): void
    {
        $this->suspicious = $suspicious;
    }

    /**
     * @return string|null
     */
    public function getSuspiciousReason(): ?string
    {
        return $this->suspiciousReason;
    }

    /**
     * @param string|null $suspiciousReason
     */
    public function setSuspiciousReason(?string $suspiciousReason): void
    {
        $this->suspiciousReason = $suspiciousReason;
    }

    /**
     * @param string $state
     * @param string $createdAt
     *
     * @return static
     */
    public static function create(string $state, string $createdAt): self
    {
        $self = new self();
        $self->setState($state);
        $self->setCreatedAt($createdAt);

        return $self;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState(string $state): void
    {
        $this->state = $state;
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
