<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\Helper;

use NewApiBundle\Enum\EnumValueNoFoundException;

/**
 * TODO: make unit tests
 */
class EnumsBuilder
{
    /** @var string */
    private $enumClassName;
    /** @var bool */
    private $nullToEmptyArrayTransformation = false;
    private $explodeDelimiters = [',', ';'];

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

    /**
     * @param string[] $explodeDelimiters
     */
    public function setExplodeDelimiters(array $explodeDelimiters): void
    {
        $this->explodeDelimiters = $explodeDelimiters;
    }

    public function buildInputValues(?iterable $apiValues): ?array
    {
        if (null === $apiValues) {
            return $this->nullToEmptyArrayTransformation ? [] : null;
        }
        $enumValues = [];
        foreach ($apiValues as $apiValue) {
            try {
                $enumValues[] = $transformed = $this->enumClassName::valueFromAPI($apiValue);
                // echo "$apiValue => $transformed\n";
            } catch (EnumValueNoFoundException $exception) {
                $enumValues[] = $apiValue;
            }
        }
        return $enumValues;
    }

    public function buildInputValuesFromExplode(?string $apiValues): ?array
    {
        if (null === $apiValues) {
            return $this->nullToEmptyArrayTransformation ? [] : null;
        }
        $apiValueCandidates = [$apiValues];
        foreach ($this->explodeDelimiters as $delimiter) {
            $apiValueCandidates = $this->explode($apiValueCandidates, $delimiter);
        }
        return $this->buildInputValues($apiValueCandidates);
    }

    private function explode(iterable $values, $delimiter): iterable
    {
        foreach ($values as $value) {
            foreach (explode($delimiter, $value) as $shard) {
                if (strlen(trim($shard))>0) yield trim($shard);
            }
        }
    }
}
