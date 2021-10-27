<?php
declare(strict_types=1);

namespace NewApiBundle\Entity\Helper;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Enum\SourceType;

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

    /**
     * @param string $source
     */
    public function setSource(string $source): void
    {
        $this->source = $source;
    }

}
