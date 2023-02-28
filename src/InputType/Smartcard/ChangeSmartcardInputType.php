<?php

declare(strict_types=1);

namespace InputType\Smartcard;

use DateTime;
use Request\InputTypeInterface;
use Validator\Constraints\Enum;
use Validator\Constraints\Iso8601;

class ChangeSmartcardInputType implements InputTypeInterface
{
    /**
     * @Enum(enumClass="Enum\SmartcardStates")
     */
    private ?string $state = null;

    /**
     * @Iso8601
     */
    private ?DateTime $createdAt;

    /**
     *
     * @return static
     */
    public static function create(string $state, DateTime $createdAt): self
    {
        $self = new self();
        $self->setState($state);
        $self->setCreatedAt($createdAt);

        return $self;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
