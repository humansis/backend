<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * Profile
 *
 * @ORM\Table(name="profile")
 * @ORM\Entity(repositoryClass="Repository\ProfileRepository")
 */
class Profile
{
    use StandardizedPrimaryKey;

    /**
     * @ORM\Column(name="photo", type="string", length=255)
     */
    private string $photo;

    /**
     * Set photo.
     *
     * @param string $photo
     *
     * @return Profile
     */
    public function setPhoto(string $photo): Profile
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * Get photo.
     *
     * @return string
     */
    public function getPhoto(): string
    {
        return $this->photo;
    }
}
