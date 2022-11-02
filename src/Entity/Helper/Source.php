<?php

declare(strict_types=1);

namespace Entity\Helper;

use Doctrine\ORM\Mapping as ORM;
use Enum\SourceType;

trait Source
{
    /**
     * @var string|null
     * @see SourceType
     *
     * @ORM\Column(name="source", type="enum_source_type", nullable=true)
     */
    private $source;

    /**
     * @return string
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }
}
