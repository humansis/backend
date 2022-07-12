<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\CountryDependent;
use NewApiBundle\Entity\Helper\CreatedAt;
use NewApiBundle\Entity\Helper\CreatedBy;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use NewApiBundle\Utils\Objects\PropertySetter;

/**
 * @ORM\Table(name="scoring_blueprint")
 * @ORM\Entity(repositoryClass="NewApiBundle\Repository\ScoringBlueprintRepository")
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    private $name;

    /**
     * @var bool
     *
     * @ORM\Column(name="archived", type="boolean", nullable=false)
     */
    private $archived = false;

    /**
     * @var string|resource
     *
     * @ORM\Column(name="content", type="blob", nullable=false)
     */
    private $content;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return ScoringBlueprint
     */
    public function setName(string $name): ScoringBlueprint
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return bool
     */
    public function isArchived(): bool
    {
        return $this->archived;
    }

    /**
     * @param bool $archived
     *
     * @return ScoringBlueprint
     */
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
     * @return ScoringBlueprint
     */
    public function setContent($content): ScoringBlueprint
    {
        $this->content = $content;
        return $this;
    }









}
