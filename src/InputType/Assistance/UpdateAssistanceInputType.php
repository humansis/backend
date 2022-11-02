<?php

declare(strict_types=1);

namespace InputType\Assistance;

use DateTimeInterface;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Utils\DateTime\Iso8601Converter;

#[Assert\GroupSequence(['UpdateAssistanceInputType', 'Strict'])]
class UpdateAssistanceInputType implements InputTypeInterface
{
    private const UNSET_STRING = 'undefined';
    private const UNSET_NUMBER = 0;

    #[Assert\Type(type: 'bool')]
    private ?bool $validated = null;

    #[Assert\Type(type: 'bool')]
    private bool $completed = false;

    #[Assert\DateTime]
    #[Assert\NotBlank(allowNull: true)]
    private ?\DateTimeInterface $dateDistribution = null;

    private ?string $originalDateDistribution = null;

    private string|\DateTimeInterface|null $dateExpiration = self::UNSET_STRING;

    private ?string $originalDateExpiration = null;

    #[Assert\DateTime]
    #[Assert\NotBlank(allowNull: true)]
    private ?\DateTimeInterface $dateExpirationToSave = null;

    private string|int|null $round = self::UNSET_STRING;

    #[Assert\Range(min: 1, max: 99, notInRangeMessage: 'Supported round range is from {{ min }} to {{ max }}.')]
    private ?int $roundToSave = null;

    private int|string|null $note = self::UNSET_NUMBER;

    /**
     * @var string|null
     */
    #[Assert\Type(type: 'string')]
    #[Assert\NotBlank(allowNull: true)]
    private $noteToSave;

    #[Assert\IsTrue(groups: 'Strict', message: 'Expiration date is not in valid format. Valid format is Y-m-d\TH:i:sP')]
    public function isValidExpirationDate(): bool
    {
        if (is_null($this->originalDateExpiration)) {
            return true;
        }
        if (is_null(Iso8601Converter::toDateTime($this->originalDateExpiration))) {
            return false;
        }

        return true;
    }

    #[Assert\IsTrue(groups: 'Strict', message: 'Distribution date is not in valid format. Valid format is Y-m-d\TH:i:sP')]
    public function isValidDateDistribution(): bool
    {
        if (is_null($this->originalDateDistribution)) {
            return true;
        }
        if (is_null(Iso8601Converter::toDateTime($this->originalDateDistribution))) {
            return false;
        }

        return true;
    }

    public function getValidated(): ?bool
    {
        return $this->validated;
    }

    public function setValidated(?bool $validated): void
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

    public function getDateDistribution(): ?DateTimeInterface
    {
        return $this->dateDistribution;
    }

    public function setDateDistribution(?string $dateDistribution): void
    {
        $this->originalDateDistribution = $dateDistribution;
        $this->dateDistribution = $dateDistribution ? Iso8601Converter::toDateTime($dateDistribution) : null;
    }

    public function getDateExpiration(): ?DateTimeInterface
    {
        return $this->dateExpirationToSave;
    }

    public function setDateExpiration(?string $dateExpiration): void
    {
        $this->originalDateExpiration = $dateExpiration;
        $this->dateExpiration = $dateExpiration ? Iso8601Converter::toDateTime($dateExpiration) : null;
        $this->dateExpirationToSave = $this->dateExpiration;
    }

    public function getRound(): ?int
    {
        return $this->roundToSave;
    }

    public function setRound(?int $round): void
    {
        $this->round = $round;
        $this->roundToSave = $round;
    }

    public function getNote(): ?string
    {
        return $this->noteToSave;
    }

    public function setNote(?string $note): void
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
        return !is_string($this->dateExpiration);
    }

    public function hasNote(): bool
    {
        return !is_int($this->note);
    }

    public function hasRound(): bool
    {
        return !is_string($this->round);
    }
}
