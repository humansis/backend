<?php

declare(strict_types=1);

namespace InputType\Assistance;

use DateTimeInterface;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Utils\DateTime\Iso8601Converter;

/**
 * @Assert\GroupSequence({"UpdateAssistanceInputType", "Strict"})
 */
class UpdateAssistanceInputType implements InputTypeInterface
{
    private const UNSET_STRING = 'undefined';
    private const UNSET_NUMBER = 0;

    /**
     * @var bool|null
     * @Assert\Type(type="bool")
     */
    private $validated = null;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $completed = false;

    /**
     * @var DateTimeInterface|null
     * @Assert\DateTime
     * @Assert\NotBlank(allowNull=true)
     */
    private $dateDistribution = null;

    /**
     * @var string
     */
    private $originalDateDistribution;

    /**
     * @var DateTimeInterface|null|string
     */
    private $dateExpiration = self::UNSET_STRING;

    /**
     * @var string
     */
    private $originalDateExpiration;

    /**
     * @var DateTimeInterface|null
     * @Assert\DateTime
     * @Assert\NotBlank(allowNull=true)
     */
    private $dateExpirationToSave;

    /**
     * @var string|int|null
     */
    private $round = self::UNSET_STRING;

    /**
     * @var int|null
     * @Assert\Range(min="1", max="99", notInRangeMessage="Supported round range is from {{ min }} to {{ max }}.")
     */
    private $roundToSave;

    /**
     * @var string|null|int
     */
    private $note = self::UNSET_NUMBER;

    /**
     * @var string|null
     * @Assert\Type(type="string")
     * @Assert\NotBlank(allowNull=true)
     */
    private $noteToSave;

    /**
     * @Assert\IsTrue(groups="Strict", message="Expiration date is not in valid format. Valid format is Y-m-d\TH:i:sP")
     * @return bool
     */
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

    /**
     * @Assert\IsTrue(groups="Strict", message="Distribution date is not in valid format. Valid format is Y-m-d\TH:i:sP")
     * @return bool
     */
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

    /**
     * @return bool|null
     */
    public function getValidated(): ?bool
    {
        return $this->validated;
    }

    /**
     * @param bool|null $validated
     */
    public function setValidated(?bool $validated): void
    {
        $this->validated = $validated;
    }

    /**
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->completed;
    }

    /**
     * @param bool $completed
     */
    public function setCompleted(bool $completed): void
    {
        $this->completed = $completed;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getDateDistribution(): ?DateTimeInterface
    {
        return $this->dateDistribution;
    }

    /**
     * @param string|null $dateDistribution
     */
    public function setDateDistribution(?string $dateDistribution): void
    {
        $this->originalDateDistribution = $dateDistribution;
        $this->dateDistribution = $dateDistribution ? Iso8601Converter::toDateTime($dateDistribution) : null;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getDateExpiration(): ?DateTimeInterface
    {
        return $this->dateExpirationToSave;
    }

    /**
     * @param string|null $dateExpiration
     */
    public function setDateExpiration(?string $dateExpiration): void
    {
        $this->originalDateExpiration = $dateExpiration;
        $this->dateExpiration = $dateExpiration ? Iso8601Converter::toDateTime($dateExpiration) : null;
        $this->dateExpirationToSave = $this->dateExpiration;
    }

    /**
     * @return int|null
     */
    public function getRound(): ?int
    {
        return $this->roundToSave;
    }

    /**
     * @param int|null $round
     */
    public function setRound(?int $round): void
    {
        $this->round = $round;
        $this->roundToSave = $round;
    }

    /**
     * @return string|null
     */
    public function getNote(): ?string
    {
        return $this->noteToSave;
    }

    /**
     * @param string|null $note
     */
    public function setNote(?string $note): void
    {
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
