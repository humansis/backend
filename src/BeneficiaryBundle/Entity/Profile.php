<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Profile
 *
 * @ORM\Table(name="profile")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\ProfileRepository")
 */
class Profile
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="photo", type="string", length=255)
     *
     */
    private $photo;


    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set photo.
     *
     * @param string $photo
     *
     * @return Profile
     */
    public function setPhoto($photo)
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * Get photo.
     *
     * @return string
     */
    public function getPhoto()
    {
        return $this->photo;
    }
}
