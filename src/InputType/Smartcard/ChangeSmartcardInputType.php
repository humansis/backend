<?php

declare(strict_types=1);

namespace InputType\Smartcard;

use DateTime;
use DateTimeInterface;
use Request\InputTypeInterface;
use Validator\Constraints\Enum;
use Symfony\Component\Validator\Constraints as Assert;

class ChangeSmartcardInputType implements InputTypeInterface
{
    /**
     * @Enum(enumClass="Enum\SmartcardStates")
     * @var string
     */
    private $state;

    /**
     * @Assert\DateTime()
     * @var DateTimeInterface
     */
    private $createdAt;

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
