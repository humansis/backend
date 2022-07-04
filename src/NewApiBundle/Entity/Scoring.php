<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\CountryDependent;
use NewApiBundle\Entity\Helper\CreatedAt;
use NewApiBundle\Entity\Helper\CreatedBy;
use UserBundle\Entity\User;

/**
 * @ORM\Table(name="scoring")
 * @ORM\Entity(repositoryClass="NewApiBundle\Repository\ScoringRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Scoring
{

    use CreatedAt;
    use CreatedBy;
    use CountryDependent;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;


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
     * @var string
     *
     * @ORM\Column(name="content", type="blob", nullable=false)
     */
    private $content;

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
     * @return Scoring
     */
    public function setName(string $name): Scoring
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
     * @return Scoring
     */
    public function setArchived(bool $archived): Scoring
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return Scoring
     */
    public function setContent(string $content): Scoring
    {
        $this->content = $content;

        return $this;
    }









}
