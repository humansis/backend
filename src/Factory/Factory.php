<?php declare(strict_types=1);

namespace Factory;

interface Factory
{
    /**
     * @return object
     */
    public function create(): object;
}
