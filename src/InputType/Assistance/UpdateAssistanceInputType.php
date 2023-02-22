<?php

declare(strict_types=1);

namespace InputType\Assistance;

use DateTimeInterface;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Utils\DateTime\Iso8601Converter;
use Validator\Constraints\Iso8601;

#[Assert\GroupSequence(['UpdateAssistanceInputType', 'Strict'])]
class UpdateAssistanceInputType implements InputTypeInterface
{
    private const UNSET_STRING = 'undefined';
    private const UNSET_NUMBER = 0;

    #[Assert\Type(type: 'bool')]
    private bool | null $validated = null;

    #[Assert\Type(type: 'bool')]
    private bool $completed = false;

    #[Assert\Date]
    #[Assert\NotBlank(allowNull: true)]
    private string | null $dateDistribution = null;

    private string | null $dateExpiration = self::UNSET_STRING;

    #[Assert\NotBlank(allowNull: true)]
    #[Iso8601]
    private string | null $dateExpirationToSave = null;

    private string | int | null $round = self::UNSET_STRING;

    #[Assert\Range(notInRangeMessage: 'Supported round range is from {{ min }} to {{ max }}.', min: 1, max: 99)]
    private int | null $roundToSave = null;

    private int | string | null $note = self::UNSET_NUMBER;

    private string|null $name = null;

    /**
     * @var string|null
     */
    #[Assert\Type(type: 'string')]
    #[Assert\NotBlank(allowNull: true)]
    private string | null $noteToSave;

    public function getValidated(): bool | null
    {
        return $this->validated;
    }

    public function setValidated(bool | null $validated): void
    {
        $this->validated = $validated;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): void
    {
        $this->completed = $completed;
    }

    public function getDateDistribution(): DateTimeInterface | null
    {
        return $this->dateDistribution ? Iso8601Converter::toDateTime($this->dateDistribution) : null;
    }

    public function setDateDistribution(string | null $dateDistribution): void
    {
        $this->dateDistribution = $dateDistribution;
    }

    public function getDateExpiration(): DateTimeInterface | null
    {
        return $this->dateExpirationToSave ? Iso8601Converter::toDateTime($this->dateExpirationToSave) : null;
    }

    public function setDateExpiration(string $dateExpiration): void
    {
        $this->dateExpiration = $dateExpiration;
        $this->dateExpirationToSave = $this->dateExpiration;
    }

    public function getRound(): int | null
    {
        return $this->roundToSave;
    }

    public function setRound(int | null $round): void
    {
        $this->round = $round;
        $this->roundToSave = $round;
    }

    public function getNote(): string | null
    {
        return $this->noteToSave;
    }

    public function setNote(string | null $note): void
    {
        $note = ($note === "") ? null : $note;

        $this->note = $note;
        $this->noteToSave = $note;
    }

    public function hasValidated(): bool
    {
        return is_bool($this->validated);
    }

    public function hasDateDistribution(): bool
    {
        return !is_null($this->dateDistribution);
    }

    public function hasDateExpiration(): bool
    {
        return $this->dateExpiration !== self::UNSET_STRING;
    }

    public function hasNote(): bool
    {
        return !is_int($this->note);
    }

    public function hasRound(): bool
    {
        return !is_string($this->round);
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function hasName(): bool
    {
        return !is_null($this->name);
    }
}
