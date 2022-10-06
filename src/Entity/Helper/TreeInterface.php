<?php

declare(strict_types=1);

namespace Entity\Helper;

interface TreeInterface
{
    public function getParent(): ?self;

    public function getChildren(): iterable;
}
