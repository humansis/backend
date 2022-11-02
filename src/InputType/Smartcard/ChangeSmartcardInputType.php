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
     */
    private ?string $state = null;

    /**
     * @var DateTimeInterface
     */
    #[Assert\DateTime]
    private $createdAt;

    /**
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

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = DateTime::createFromFormat('Y-m-d\TH:i:sO', $createdAt);
    }
}
