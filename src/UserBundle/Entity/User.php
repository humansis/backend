<?php

namespace UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use UserBundle\Utils\ExportableInterface;

/**
 * User
 *
 * @ORM\Table(name="`user")
 * @ORM\Entity(repositoryClass="UserBundle\Repository\UserRepository")
 */
class User extends BaseUser
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"FullUser"})
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="UserBundle\Entity\UserCountry", mappedBy="user")
     * @Groups({"FullUser"})
     */
    private $countries;

    /**
     * @ORM\OneToMany(targetEntity="UserBundle\Entity\UserProject", mappedBy="user")
     * @Groups({"FullUser"})
     */
    private $userProjects;

    /**
     * @var string
     * @Groups({"FullUser"})
     * @Assert\NotBlank(message="Email can't be empty")
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email.",
     *     checkMX = true
     * )
     */
    protected $email;

    /**
     * @var string
     * @Groups({"FullUser"})
     * @Assert\NotBlank(message="Username can't be empty")
     * @Assert\Length(
     *      min = 2,
     *      max = 50,
     *      minMessage = "Your username must be at least {{ limit }} characters long",
     *      maxMessage = "Your username cannot be longer than {{ limit }} characters"
     * )
     */
    protected $username;

    /**
     * @var array
     * @Groups({"FullUser"})
     */
    protected $roles;



    public function __construct()
    {
        parent::__construct();
    }

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
     * Add country.
     *
     * @param \UserBundle\Entity\UserCountry $country
     *
     * @return User
     */
    public function addCountry(\UserBundle\Entity\UserCountry $country)
    {
        $this->countries[] = $country;

        return $this;
    }

    /**
     * Remove country.
     *
     * @param \UserBundle\Entity\UserCountry $country
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCountry(\UserBundle\Entity\UserCountry $country)
    {
        return $this->countries->removeElement($country);
    }

    /**
     * Get countries.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCountries()
    {
        return $this->countries;
    }

    /**
     * Add userProject.
     *
     * @param \UserBundle\Entity\UserProject $userProject
     *
     * @return User
     */
    public function addUserProject(\UserBundle\Entity\UserProject $userProject)
    {
        $this->userProjects[] = $userProject;

        return $this;
    }

    /**
     * Remove userProject.
     *
     * @param \UserBundle\Entity\UserProject $userProject
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeUserProject(\UserBundle\Entity\UserProject $userProject)
    {
        return $this->userProjects->removeElement($userProject);
    }

    /**
     * Get userProjects.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserProjects()
    {
        return $this->userProjects;
    }

    /**
     * Returns an array representation of this class in order to prepare the export
     * @return array
     */
    /*function getMappedValueForExport(): array
    {
        // récuperer les e-mails des utilisateurs :

        $valuesMail = [];
        foreach ($this->getEmail() as $value) {
            dump($this->getEmail());
            array_push($valuesMail, $value);
        }
        $valuesMail = join(',', $valuesMail);

        // récuperer les rôles depuis l'objet roles

        $valuesRoles = [];
        foreach ($this->getRoles()->getValues() as $value) {
            array_push($valuesRoles, $value->getFieldString());
        }
        $valuesRoles = join(',', $valuesRoles);

        // récuperer les nationalID depuis l'objet les nationalID

        $valuesnationalID = [];

        foreach ($this->getNationalIds()->getValues() as $value) {
            array_push($valuesnationalID, $value->getIdNumber());
        }
        $valuesnationalID = join(',',$valuesnationalID);


        // récuperer les adm1 , adm2 , adm3 , adm 4 depuis l'objet localisation : faut vérifier d'abord s'ils sont null ou pas pour avoir le nom

        $adm1 = ( ! empty($this->getHousehold()->getLocation()->getAdm1()) ) ? $this->getHousehold()->getLocation()->getAdm1()->getName() : '';
        $adm2 = ( ! empty($this->getHousehold()->getLocation()->getAdm2()) ) ? $this->getHousehold()->getLocation()->getAdm2()->getName() : '';
        $adm3 = ( ! empty($this->getHousehold()->getLocation()->getAdm3()) ) ? $this->getHousehold()->getLocation()->getAdm3()->getName() : '';
        $adm4 = ( ! empty($this->getHousehold()->getLocation()->getAdm4()) ) ? $this->getHousehold()->getLocation()->getAdm4()->getName() : '';



        return [
            "Address_street" => $this->getHousehold()->getAddressStreet(),
            "Address_number" => $this->getHousehold()->getAddressNumber(),
            "Address_postcode" => $this->getHousehold()->getAddressPostcode(),
            "livelihood" => $this->getHousehold()->getLivelihood(),
            "notes" => $this->getHousehold()->getNotes(),
            "lat" => $this->getHousehold()->getLatitude(),
            "long" => $this->getHousehold()->getLongitude(),
            "adm1" => $adm1,
            "adm2" =>$adm2,
            "adm3" =>$adm3,
            "adm4" =>$adm4,
            "Given name" => $this->getGivenName(),
            "Family name"=> $this->getFamilyName(),
            "Gender" => $this->getGender(),
            "Status" => $this->getStatus(),
            "Date of birth" => $this->getDateOfBirth()->format('m/d/y'),
            "Vulnerability criteria" => $valuescriteria,
            "Phones" => $valuesphones ,
            "National IDs" => $valuesnationalID,
        ];



    }*/
}
