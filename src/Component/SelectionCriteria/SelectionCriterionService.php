<?php

declare(strict_types=1);

namespace Component\SelectionCriteria;

use BadMethodCallException;
use Component\SelectionCriteria\Structure\Field;
use Enum\SelectionCriteriaTarget;
use InvalidArgumentException;

class SelectionCriterionService
{
    /**
     * @param \Component\SelectionCriteria\FieldGeneratorInterface[] $generators
     */
    public function __construct(private readonly array $generators)
    {
    }

    /**
     *
     * @return Field[]
     * @throws InvalidArgumentException
     * @throws BadMethodCallException
     */
    public function findFieldsByTarget(string $target, string $countryIso3): iterable
    {
        if (!in_array($target, SelectionCriteriaTarget::values())) {
            throw new InvalidArgumentException($target . ' is not valid Selection criterion target.');
        }

        $data = [];

        $generator = $this->getGenerator($target);
        foreach ($generator->generate($countryIso3) as $field) {
            $data[] = $field;
        }

        return $data;
    }

    /**
     *
     * @throws InvalidArgumentException
     * @throws BadMethodCallException
     */
    public function findFieldConditions(string $fieldCode, string $target, string $countryIso3): array
    {
        $generator = $this->getGenerator($target);
        foreach ($generator->generate($countryIso3) as $field) {
            if ($fieldCode === $field->getCode()) {
                return $field->getConditions();
            }
        }

        throw new InvalidArgumentException('Field ' . $fieldCode . ' for country ' . $countryIso3 . ' does not exists');
    }

    /**
     *
     *
     * @throws BadMethodCallException
     */
    private function getGenerator(string $target): FieldGeneratorInterface
    {
        foreach ($this->generators as $generator) {
            if ($generator->supports($target)) {
                return $generator;
            }
        }

        throw new BadMethodCallException("No generator exists for target '$target'");
    }
}
