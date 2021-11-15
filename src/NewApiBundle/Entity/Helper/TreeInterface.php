<?php
declare(strict_types=1);

namespace NewApiBundle\Entity\Helper;

interface TreeInterface
{
    public function getParent(): ?self;
    public function getChildren(): array;
}
