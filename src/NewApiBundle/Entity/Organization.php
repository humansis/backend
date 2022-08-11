<?php

namespace NewApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Organization
 *
 * @ORM\Table(name="organization")
 * @ORM\Entity(repositoryClass="NewApiBundle\Repository\OrganizationRepository")
 */
class Organization
{
    /**
    * @var int
    *
    * @ORM\Column(name="id", type="integer")
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    *
    * @SymfonyGroups({"FullOrganization"})
    */
    private $id;


    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @SymfonyGroups({"FullOrganization"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="logo", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullOrganization"})
     */
    private $logo;

    /**
     * @var string
     *
     * @ORM\Column(name="font", type="string", length=255)
     * @SymfonyGroups({"FullOrganization"})
     */
    private $font;

    /**
     * @var string
     *
     * @ORM\Column(name="primaryColor", type="string", length=255)
     * @SymfonyGroups({"FullOrganization"})
     */
    private $primaryColor;

    /**
     * @var string
     *
     * @ORM\Column(name="secondaryColor", type="string", length=255)
     * @SymfonyGroups({"FullOrganization"})
     */
    private $secondaryColor;

    /**
     * @var string
     *
     * @ORM\Column(name="footerContent", type="string", length=255)
     * @SymfonyGroups({"FullOrganization"})
     */
    private $footerContent;

    /**
     * @var OrganizationServices $organizationServices
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\OrganizationServices", mappedBy="organization", cascade={"remove"})
     */
    private $organizationServices;


    /**
     * Set id.
     *
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }


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
     * Set name.
     *
     * @param string $name
     *
     * @return Organization
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
    * Set logo.
    *
    * @param string $logo
    *
    * @return Organization
    */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo.
     *
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set font.
     *
     * @param string $font
     *
     * @return Organization
     */
    public function setFont($font)
    {
        $this->font = $font;

        return $this;
    }

    /**
     * Get font.
     *
     * @return string
     */
    public function getFont()
    {
        return $this->font;
    }

    /**
     * Set primaryColor.
     *
     * @param string $primaryColor
     *
     * @return Organization
     */
    public function setPrimaryColor($primaryColor)
    {
        $this->primaryColor = $primaryColor;

        return $this;
    }

    /**
     * Get primaryColor.
     *
     * @return string
     */
    public function getPrimaryColor()
    {
        return $this->primaryColor;
    }

    /**
     * Set secondaryColor.
     *
     * @param string $secondaryColor
     *
     * @return Organization
     */
    public function setSecondaryColor($secondaryColor)
    {
        $this->secondaryColor = $secondaryColor;

        return $this;
    }

    /**
     * Get secondaryColor.
     *
     * @return string
     */
    public function getSecondaryColor()
    {
        return $this->secondaryColor;
    }

    /**
    * Set footerContent.
    *
    * @param string $footerContent
    *
    * @return Organization
    */
    public function setFooterContent($footerContent)
    {
        $this->footerContent = $footerContent;

        return $this;
    }

    /**
     * Get footerContent.
     *
     * @return string
     */
    public function getFooterContent()
    {
        return $this->footerContent;
    }

    /**
     * Add OrganizationServices.
     *
     * @param \NewApiBundle\Entity\OrganizationServices $organizationServices
     *
     * @return OrganizationServices
     */
    public function addOrganizationServices(\NewApiBundle\Entity\OrganizationServices $organizationServices)
    {
        if (null === $this->organizationServices) {
            $this->organizationServices = new \Doctrine\Common\Collections\ArrayCollection();
        }
        $this->organizationServices[] = $organizationServices;

        return $this;
    }

    /**
     * Remove OrganizationServices.
     *
     * @param \NewApiBundle\Entity\OrganizationServices $organizationServices
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeOrganizationServices(\NewApiBundle\Entity\OrganizationServices $organizationServices)
    {
        return $this->organizationServices->removeElement($organizationServices);
    }

    /**
     * Get OrganizationServices.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrganizationServices()
    {
        return $this->organizationServices;
    }
}
