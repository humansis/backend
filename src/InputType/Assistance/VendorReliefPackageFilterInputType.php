<?php

declare(strict_types=1);

namespace InputType\Assistance;

use DateTime;
use DateTimeZone;
use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;
use Utils\DateTime\Iso8601Converter;
use Validator\Constraints\Iso8601;

/**
 * @Assert\GroupSequence({"VendorReliefPackageFilterInputType", "Strict"})
 */
class VendorReliefPackageFilterInputType extends AbstractFilterInputType
{
    /**
     * @Iso8601
     */
    protected $lastModifiedFrom;


    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Choice(callback={"Enum\ReliefPackageState", "values"})
     *     },
     *     groups={"Strict"}
     *     )
     */
    protected $states;

    public function hasLastModifiedFrom(): bool
    {
        return $this->has('lastModifiedFrom');
    }

    public function getLastModifiedFrom(): string
    {
        return $this->lastModifiedFrom;
    }

    public function getLastModifiedFromAsUtcDateTime(): \DateTimeInterface
    {
        return DateTime::createFromFormat(\DateTimeInterface::ATOM, $this->lastModifiedFrom)->setTimezone(new DateTimeZone("UTC"));
    }

    public function hasStates(): bool
    {
        return $this->has('states');
    }

    public function getStates(): array
    {
        return $this->states;
    }
}
