<?php

/** @noinspection PhpMissingParamTypeInspection */
/** @noinspection PhpMissingReturnTypeInspection */
/** @noinspection PhpMissingFieldTypeInspection */

declare(strict_types=1);

namespace InputType\Assistance;

use DateTimeInterface;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Utils\DateTime\Iso8601Converter;

#[Assert\GroupSequence(['AssistanceUpdateInputType', 'Strict'])]
class AssistanceUpdateInputType implements InputTypeInterface
{
    private const UNSET_STRING = 'undefined';
    private const UNSET_NUMBER = 0;

    #[Assert\Type(type: 'bool')]
    private $validated = null;

    #[Assert\Type(type: 'bool')]
    private $completed = false;

    /** @var string | null  */
    #[Assert\Date]
    #[Assert\NotBlank(allowNull: true)]
    private $dateDistribution = null;

    private $dateExpiration = self::UNSET_STRING;

    /** @var string | null  */
    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Date]
    private $dateExpirationToSave = null;

    private $round = self::UNSET_STRING;

    #[Assert\Range(notInRangeMessage: 'Supported round range is from {{ min }} to {{ max }}.', min: 1, max: 99)]
    private $roundToSave = null;

    private $note = self::UNSET_NUMBER;

    #[Assert\Type('string')]
    private $name = null;

    /**
     * @var string|null
     */
    #[Assert\Type(type: 'string')]
    #[Assert\NotBlank(allowNull: true)]
    private $noteToSave;

    /**
     * @return bool | null
     */
    public function getValidated()
    {
        return $this->validated;
    }

    /**
     * @param $validated bool | null
     */
    public function setValidated($validated): void
    {
        $this->validated = $validated;
    }

    /**
     * @return bool | null
     */
    public function isCompleted()
    {
        return $this->completed;
    }

    /**
     * @param $completed bool | null
     */
    public function setCompleted($completed): void
    {
        $this->completed = $completed;
    }

    public function getDateDistribution(): DateTimeInterface | null
    {
        return $this->dateDistribution ? Iso8601Converter::toDateTime($this->dateDistribution, true) : null;
    }

    public function setDateDistribution(string | null $dateDistribution): void
    {
        $this->dateDistribution = $dateDistribution;
    }

    public function getDateExpiration(): DateTimeInterface | null
    {
        return $this->dateExpirationToSave ? Iso8601Converter::toDateTime($this->dateExpirationToSave, true) : null;
    }

    public function setDateExpiration(string $dateExpiration): void
    {
        $this->dateExpiration = $dateExpiration;
        $this->dateExpirationToSave = $this->dateExpiration;
    }

    /**
     * @return int | null
     */
    public function getRound()
    {
        return $this->roundToSave;
    }

    /**
     * @param $round int | null
     */
    public function setRound($round): void
    {
        $this->round = $round;
        $this->roundToSave = $round;
    }

    /**
     * @return string | null
     */
    public function getNote()
    {
        return $this->noteToSave;
    }

    /**
     * @param $note string | null
     */
    public function setNote($note): void
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    public function hasName(): bool
    {
        return !is_null($this->name);
    }
}
