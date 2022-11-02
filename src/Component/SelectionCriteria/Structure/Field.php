<?php

declare(strict_types=1);

namespace Component\SelectionCriteria\Structure;

use InvalidArgumentException;

class Field
{
    private readonly array $conditions;

    private readonly string|array $type;

    /** @var callable|null */
    private $callback;

    public function __construct(
        private readonly string $code,
        private readonly string $label,
        array $conditions,
        string $type,
        ?callable $callback = null
    ) {
        if (count($conditions) <= 0) {
            throw new InvalidArgumentException('Argument 3 is not valid array. Conditions must be non empty value');
        }

        if ($callback && !is_callable($callback)) {
            throw new InvalidArgumentException('Argument 5 is not valid callback');
        }

        if (null === $callback) {
            $callback = 'boolean' === $type ? 'is_bool' : 'is_' . $type;
            if ('is_integer' === $callback || 'is_int' === $callback) {
                $callback = fn($integerString) => is_integer($integerString) || (
                        is_numeric($integerString) && (string) intval($integerString) === $integerString
                    );
            } else {
                if (!function_exists($callback)) {
                    throw new InvalidArgumentException('Argument 5 missing. Callback is necessary for type ' . $type);
                }
            }
        }
        $this->conditions = $conditions;
        $this->type = $type;
        $this->callback = $callback;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isValid($value): bool
    {
        return (bool) call_user_func($this->callback, $value);
    }
}
