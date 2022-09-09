<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Profile
 *
 * @ORM\Table(name="profile")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\ProfileRepository")
 */
class Profile
{
    use StandardizedPrimaryKey;

    /**
     * @var string
     *
     * @ORM\Column(name="photo", type="string", length=255)
     * @SymfonyGroups({"FullHousehold"})
     */
    private $photo;


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
