<?php

namespace BeneficiaryBundle\Entity;

use DistributionBundle\Entity\Location;
use Doctrine\ORM\Mapping as ORM;

/**
 * Household
 *
 * @ORM\Table(name="household")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\HouseholdRepository")
 */
class Household
{
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
     * @ORM\Column(name="address_street", type="string", length=255)
     */
    private $addressStreet;

    /**
     * @var string
     *
     * @ORM\Column(name="address_number", type="string", length=255)
     */
    private $addressNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="address_postcode", type="string", length=255)
     */
    private $addressPostcode;

    /**
     * @var int
     *
     * @ORM\Column(name="livelihood", type="integer")
     */
    private $livelihood;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="string", length=255)
     */
    private $notes;

    /**
     * @var string
     *
     * @ORM\Column(name="latitude", type="string", length=45)
     */
    private $latitude;

    /**
     * @var string
     *
     * @ORM\Column(name="longitude", type="string", length=45)
     */
    private $longitude;

    /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\Location")
     */
    private $location;

    /**
     * @var CountrySpecificAnswer
     *
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\CountrySpecificAnswer", mappedBy="household")
     */
    private $countrySpecificAnswers;

    /**
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\Beneficiary", mappedBy="household")
     */
    private $beneficiaries;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->countrySpecificAnswers = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set photo.
     *
     * @param string $photo
     *
     * @return Household
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

    /**
     * Set addressStreet.
     *
     * @param string $addressStreet
     *
     * @return Household
     */
    public function setAddressStreet($addressStreet)
    {
        $this->addressStreet = $addressStreet;

        return $this;
    }

    /**
     * Get addressStreet.
     *
     * @return string
     */
    public function getAddressStreet()
    {
        return $this->addressStreet;
    }

    /**
     * Set addressNumber.
     *
     * @param string $addressNumber
     *
     * @return Household
     */
    public function setAddressNumber($addressNumber)
    {
        $this->addressNumber = $addressNumber;

        return $this;
    }

    /**
     * Get addressNumber.
     *
     * @return string
     */
    public function getAddressNumber()
    {
        return $this->addressNumber;
    }

    /**
     * Set addressPostcode.
     *
     * @param string $addressPostcode
     *
     * @return Household
     */
    public function setAddressPostcode($addressPostcode)
    {
        $this->addressPostcode = $addressPostcode;

        return $this;
    }

    /**
     * Get addressPostcode.
     *
     * @return string
     */
    public function getAddressPostcode()
    {
        return $this->addressPostcode;
    }

    /**
     * Set livelihood.
     *
     * @param int $livelihood
     *
     * @return Household
     */
    public function setLivelihood($livelihood)
    {
        $this->livelihood = $livelihood;

        return $this;
    }

    /**
     * Get livelihood.
     *
     * @return int
     */
    public function getLivelihood()
    {
        return $this->livelihood;
    }

    /**
     * Set notes.
     *
     * @param string $notes
     *
     * @return Household
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes.
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Add countrySpecificAnswer.
     *
     * @param \BeneficiaryBundle\Entity\CountrySpecificAnswer $countrySpecificAnswer
     *
     * @return Household
     */
    public function addCountrySpecificAnswer(\BeneficiaryBundle\Entity\CountrySpecificAnswer $countrySpecificAnswer)
    {
        $this->countrySpecificAnswers[] = $countrySpecificAnswer;

        return $this;
    }

    /**
     * Remove countrySpecificAnswer.
     *
     * @param \BeneficiaryBundle\Entity\CountrySpecificAnswer $countrySpecificAnswer
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCountrySpecificAnswer(\BeneficiaryBundle\Entity\CountrySpecificAnswer $countrySpecificAnswer)
    {
        return $this->countrySpecificAnswers->removeElement($countrySpecificAnswer);
    }

    /**
     * Get countrySpecificAnswers.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCountrySpecificAnswers()
    {
        return $this->countrySpecificAnswers;
    }

    /**
     * Set location.
     *
     * @param \DistributionBundle\Entity\Location|null $location
     *
     * @return Household
     */
    public function setLocation(\DistributionBundle\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return \DistributionBundle\Entity\Location|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set lat.
     *
     * @param string $latitude
     *
     * @return Household
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get lat.
     *
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set long.
     *
     * @param string $longitude
     *
     * @return Household
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get long.
     *
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Add beneficiary.
     *
     * @param \BeneficiaryBundle\Entity\Beneficiary $beneficiary
     *
     * @return Household
     */
    public function addBeneficiary(\BeneficiaryBundle\Entity\Beneficiary $beneficiary)
    {
        $this->beneficiaries[] = $beneficiary;

        return $this;
    }

    /**
     * Remove beneficiary.
     *
     * @param \BeneficiaryBundle\Entity\Beneficiary $beneficiary
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeBeneficiary(\BeneficiaryBundle\Entity\Beneficiary $beneficiary)
    {
        return $this->beneficiaries->removeElement($beneficiary);
    }

    /**
     * Get beneficiaries.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBeneficiaries()
    {
        return $this->beneficiaries;
    }

    /**
     * Set beneficiaries.
     *
     * @return Household
     */
    public function setBeneficiaries(\Doctrine\Common\Collections\Collection $collection = null)
    {
        $this->beneficiaries = $collection;

        return $this;
    }
}
