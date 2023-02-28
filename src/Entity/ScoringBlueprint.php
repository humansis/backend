<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\CountryDependent;
use Entity\Helper\CreatedAt;
use Entity\Helper\CreatedBy;
use Entity\Helper\StandardizedPrimaryKey;
use Utils\Objects\PropertySetter;

/**
 * @ORM\Table(name="scoring_blueprint")
 * @ORM\Entity(repositoryClass="Repository\ScoringBlueprintRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ScoringBlueprint
{
    use StandardizedPrimaryKey;
    use CreatedAt;
    use CreatedBy;
    use CountryDependent;
    use PropertySetter;

    /**
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    private ?string $name = null;

    /**
     * @ORM\Column(name="archived", type="boolean", nullable=false)
     */
    private bool $archived = false;

    /**
     * @var string|resource
     *
     * @ORM\Column(name="content", type="blob", nullable=false)
     */
    private $content;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): ScoringBlueprint
    {
        $this->name = $name;

        return $this;
    }

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): ScoringBlueprint
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * @return resource
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        $cache = fopen('php://memory', 'r+');
        stream_copy_to_stream($this->getContent(), $cache);
        rewind($cache);
        rewind($this->content);

        return $cache;
    }

    /**
     * @param resource $content
     */
    public function setContent($content): ScoringBlueprint
    {
        $this->content = $content;

        return $this;
    }
}
