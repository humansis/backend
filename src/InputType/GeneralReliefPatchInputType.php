<?php

declare(strict_types=1);

namespace InputType;

use Request\InputTypeInterface;
use Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

class GeneralReliefPatchInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("boolean")
     */
    private $distributed;

    /**
     * @Iso8601
     */
    private $dateOfDistribution;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $note;

    /**
     * @var array
     * @internal Main purpose is check that attribute was setup
     */
    private $isSet = [];

    /**
     * @return bool
     */
    public function getDistributed()
    {
        return $this->distributed;
    }

    /**
     * @param bool $distributed
     */
    public function setDistributed($distributed): void
    {
        $this->distributed = $distributed;
        $this->isSet[] = 'distributed';
    }

    public function isDistributedSet(): bool
    {
        return in_array('distributed', $this->isSet);
    }

    /**
     * @return string|null
     */
    public function getDateOfDistribution()
    {
        return $this->dateOfDistribution;
    }

    /**
     * @param string|null $dateOfDistribution
     */
    public function setDateOfDistribution($dateOfDistribution): void
    {
        $this->dateOfDistribution = $dateOfDistribution;
        $this->isSet[] = 'dateOfDistribution';
    }

    public function isdateOfDistributionSet(): bool
    {
        return in_array('distributed', $this->isSet);
    }

    /**
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param string $note
     */
    public function setNote($note): void
    {
        $this->note = $note;
        $this->isSet[] = 'note';
    }

    public function isNoteSet(): bool
    {
        return in_array('note', $this->isSet);
    }

    /**
     * Validation constraint to verify if distributed=true, than dateOfDistribution must be filled
     *
     * @Assert\IsTrue(message="Date of distribution must be present.")
     * @return bool
     */
    public function isdateOfDistributionValid()
    {
        if ($this->distributed) {
            return (bool) $this->dateOfDistribution;
        }

        return true;
    }
}
