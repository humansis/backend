<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CountrySpecificAnswer
 *
 * @ORM\Table(name="country_specific_answer")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\CountrySpecificAnswerRepository")
 */
class CountrySpecificAnswer
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
     * @ORM\Column(name="answer", type="string", length=255)
     */
    private $answer;

    /**
     * @var CountrySpecific
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\CountrySpecific", inversedBy="countrySpecificAnswers")
     */
    private $countrySpecific;

    /**
     * @var BeneficiaryProfile
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\BeneficiaryProfile", inversedBy="countrySpecificAnswers")
     */
    private $beneficiaryProfile;

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
     * Set answer.
     *
     * @param string $answer
     *
     * @return CountrySpecificAnswer
     */
    public function setAnswer($answer)
    {
        $this->answer = $answer;

        return $this;
    }

    /**
     * Get answer.
     *
     * @return string
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    /**
     * Set countrySpecific.
     *
     * @param \BeneficiaryBundle\Entity\CountrySpecific|null $countrySpecific
     *
     * @return CountrySpecificAnswer
     */
    public function setCountrySpecific(\BeneficiaryBundle\Entity\CountrySpecific $countrySpecific = null)
    {
        $this->countrySpecific = $countrySpecific;

        return $this;
    }

    /**
     * Get countrySpecific.
     *
     * @return \BeneficiaryBundle\Entity\CountrySpecific|null
     */
    public function getCountrySpecific()
    {
        return $this->countrySpecific;
    }

    /**
     * Set beneficiaryProfile.
     *
     * @param \BeneficiaryBundle\Entity\BeneficiaryProfile|null $beneficiaryProfile
     *
     * @return CountrySpecificAnswer
     */
    public function setBeneficiaryProfile(\BeneficiaryBundle\Entity\BeneficiaryProfile $beneficiaryProfile = null)
    {
        $this->beneficiaryProfile = $beneficiaryProfile;

        return $this;
    }

    /**
     * Get beneficiaryProfile.
     *
     * @return \BeneficiaryBundle\Entity\BeneficiaryProfile|null
     */
    public function getBeneficiaryProfile()
    {
        return $this->beneficiaryProfile;
    }
}
