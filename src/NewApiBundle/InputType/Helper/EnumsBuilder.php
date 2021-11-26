<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\Helper;

use NewApiBundle\Enum\EnumValueNoFoundException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class EnumsBuilder
{
    /** @var string */
    private $enumClassName;
    /** @var bool */
    private $nullToEmptyArrayTransformation = false;

    /**
     * @param string $enumClassName
     */
    public function __construct(string $enumClassName)
    {
        $this->enumClassName = $enumClassName;
    }

    /**
     * @param bool $nullToEmptyArrayTransformation
     */
    public function setNullToEmptyArrayTransformation(bool $nullToEmptyArrayTransformation = true): void
    {
        $this->nullToEmptyArrayTransformation = $nullToEmptyArrayTransformation;
    }

    public function buildInputValue(?array $apiValues, ?string $propertyPath = null): ?array
    {
        if (null === $apiValues) {
            return $this->nullToEmptyArrayTransformation ? [] : null;
        }
        $enumValues = [];
        foreach ($apiValues as $apiValue) {
            try {
                $enumValues[] = $this->enumClassName::valueFromAPI($apiValue);
            } catch (EnumValueNoFoundException $exception) {
                $enumValues[] = $apiValue;
            }
        }
        return $enumValues;
    }
}
