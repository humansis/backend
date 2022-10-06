<?php

declare(strict_types=1);

namespace InputType;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class DonorUpdateInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     * @Assert\NotBlank()
     * @Assert\NotNull()
     */
    private $fullname;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $shortname;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $notes;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $logo;

    /**
     * @return string
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * @param string $fullname
     */
    public function setFullname($fullname)
    {
        $this->fullname = $fullname;
    }

    /**
     * @return string|null
     */
    public function getShortname()
    {
        return $this->shortname;
    }

    /**
     * @param string|null $shortname
     */
    public function setShortname($shortname)
    {
        $this->shortname = $shortname;
    }

    /**
     * @return string|null
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string|null $notes
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }

    /**
     * @return string|null
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * @param string|null $logo
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;
    }
}
