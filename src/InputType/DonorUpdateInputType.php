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
     * @Assert\NotBlank()
     * @Assert\NotNull()
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
    public function getFullname(): string
    {
        return $this->fullname;
    }

    /**
     * @param string $fullname
     */
    public function setFullname(string $fullname)
    {
        $this->fullname = $fullname;
    }

    /**
     * @return string|null
     */
    public function getShortname(): ?string
    {
        return $this->shortname;
    }

    /**
     * @param string|null $shortname
     */
    public function setShortname(?string $shortname)
    {
        $this->shortname = $shortname;
    }

    /**
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @param string|null $notes
     */
    public function setNotes(?string $notes)
    {
        $this->notes = $notes;
    }

    /**
     * @return string|null
     */
    public function getLogo(): ?string
    {
        return $this->logo;
    }

    /**
     * @param string|null $logo
     */
    public function setLogo(?string $logo)
    {
        $this->logo = $logo;
    }
}
