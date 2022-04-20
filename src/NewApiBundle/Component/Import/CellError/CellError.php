<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\CellError;

class CellError
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $property;

    private $value;

    public function __construct(string $type, string $property, $value)
    {
        $this->type = $type;
        $this->property = $property;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getProperty(): string
    {
        return $this->property;
    }

    /**
     * @param string $property
     */
    public function setProperty(string $property): void
    {
        $this->property = $property;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

}
