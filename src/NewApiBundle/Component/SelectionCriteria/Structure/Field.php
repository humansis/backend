<?php
declare(strict_types=1);

namespace NewApiBundle\Component\SelectionCriteria\Structure;

class Field
{
    /** @var string */
    private $code;

    /** @var array */
    private $conditions;

    /** @var string|array */
    private $type;

    /** @var callable|null */
    private $callback;

    public function __construct(string $code, array $conditions, string $type, ?callable $callback = null)
    {
        if (count($conditions) <= 0) {
            throw new \InvalidArgumentException('Argument 3 is not valid array. Conditions must be non empty value');
        }

        if ($callback && !is_callable($callback)) {
            throw new \InvalidArgumentException('Argument 5 is not valid callback');
        }

        if (null === $callback) {
            $callback = 'boolean' === $type ? 'is_bool' : 'is_'.$type;
            if (!function_exists($callback)) {
                throw new \InvalidArgumentException('Argument 5 missing. Callback is necessary for type '.$type);
            }
        }

        $this->code = $code;
        $this->conditions = $conditions;
        $this->type = $type;
        $this->callback = $callback;
    }

    public function getCode(): string
    {
        return $this->code;
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
