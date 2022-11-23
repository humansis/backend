<?php

declare(strict_types=1);

namespace Component\SelectionCriteria;

use Component\SelectionCriteria\Structure\Field;

interface FieldGeneratorInterface
{
    /**
     * @return Field[]
     */
    public function generate(?string $countryIso3);

    public function supports(string $target): bool;
}
