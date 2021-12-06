<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Component\Import\Enum\State;
use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ImportPatchInputType implements InputTypeInterface
{
    /**
     * @var string|null
     *
     * @Assert\Type("string")
     * @Assert\Choice(callback="allowedStates")
     */
    private $status;

    /**
     * @var string|null
     *
     * @Assert\Type("string")
     */
    private $description;

    /**
     * @var string|null
     *
     * @Assert\Type("string")
     * @Assert\NotBlank(allowNull=true)
     */
    private $title;

    /**
     * ImportUpdateStatusInputType constructor.
     *
     * @param string|null $status
     */
    public function __construct(?string $status = null)
    {
        $this->status = $status;
    }

    public static function allowedStates(): array
    {
        return [
            State::INTEGRITY_CHECKING,
            State::IDENTITY_CHECKING,
            State::SIMILARITY_CHECKING,
            State::IMPORTING,
            State::CANCELED,
        ];
    }

    /**
     * @return string|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string|null $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
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
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }
}
