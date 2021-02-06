<?php
declare(strict_types=1);

namespace NewApiBundle\Component\SelectionCriteria;

use NewApiBundle\Component\SelectionCriteria\Structure\Field;

interface FieldGeneratorInterface
{
    /**
     * @param string|null $countryIso3
     *
     * @return Field[]
     */
    public function generate(?string $countryIso3);

    /**
     * @param string $target
     *
     * @return bool
     */
    public function supports(string $target): bool;
}
