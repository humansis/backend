<?php

declare(strict_types=1);

namespace Component\Import\Exception;

use Exception;

class InvalidFormulaException extends Exception
{
    /** @var string */
    private $formula;

    public function __construct(string $formula, $message = "")
    {
        $this->formula = $formula;
        parent::__construct($message);
    }

    /**
     * @return string
     */
    public function getFormula(): string
    {
        return $this->formula;
    }

    /**
     * @param string $formula
     */
    public function setFormula(string $formula): void
    {
        $this->formula = $formula;
    }
}
