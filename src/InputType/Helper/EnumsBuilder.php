<?php

declare(strict_types=1);

namespace InputType\Helper;

use Enum\EnumValueNoFoundException;

/**
 * TODO: make unit tests
 */
class EnumsBuilder
{
    private bool $nullToEmptyArrayTransformation = false;

    private array $explodeDelimiters = [',', ';'];

    public function __construct(private readonly string $enumClassName)
    {
    }

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

    public function buildInputValues($apiValues): ?array
    {
        if (null === $apiValues) {
            return $this->nullToEmptyArrayTransformation ? [] : null;
        }
        $enumValues = [];
        if (is_string($apiValues)) {
            $apiValues = $this->buildInputValuesFromExplode($apiValues);
        }
        foreach ($apiValues as $apiValue) {
            try {
                $enumValues[] = $transformed = $this->enumClassName::valueFromAPI($apiValue);
                // echo "$apiValue => $transformed\n";
            } catch (EnumValueNoFoundException) {
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
            foreach (explode($delimiter, (string) $value) as $shard) {
                if (strlen(trim($shard)) > 0) {
                    yield trim($shard);
                }
            }
        }
    }
}
