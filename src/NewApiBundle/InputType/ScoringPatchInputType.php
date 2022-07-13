<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\InputTypeInterface;
use NewApiBundle\Utils\Objects\PropertyList;
use NewApiBundle\Validator\Constraints\IsBase64;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints as Assert;

class ScoringPatchInputType implements InputTypeInterface
{

    use PropertyList;

    /**
     * @var string
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $name;

    /**
     * @Assert\Type("bool")
     */
    private $archived;

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return ScoringPatchInputType
     */
    public function setName($name): ScoringPatchInputType
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return bool
     */
    public function isArchived(): ?bool
    {
        return $this->archived;
    }

    /**
     * @param bool $archived
     *
     * @return ScoringPatchInputType
     */
    public function setArchived(bool $archived): ScoringPatchInputType
    {
        $this->archived = $archived;

        return $this;
    }








}
