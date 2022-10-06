<?php

declare(strict_types=1);

namespace InputType\Import;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CreateInputType implements InputTypeInterface
{
    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\Length(max="64")
     * @Assert\NotBlank
     */
    private $title;

    /**
     * @var string|null
     *
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $description;

    /**
     * TODO array should not be empty (after FE implementation)
     *
     * @var int[]
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    private $projects;

    /**
     * TODO remove after FE part of PIN-2820 will be implemented
     *
     * @var int
     *
     * @Assert\Type("integer")
     */
    private $projectId;

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return int[]
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * @param int[] $projects
     */
    public function setProjects($projects)
    {
        $this->projects = $projects;
    }

    /**
     * @return int
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param int $projectId
     */
    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    }
}
