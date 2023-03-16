<?php

namespace InputType;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['UserCreateInputType', 'Strict'])]
class UserCreateInputType implements InputTypeInterface
{
    #[Assert\Length(max: 180)]
    #[Assert\Type('string')]
    #[Assert\NotBlank]
    private ?string $email = null;

    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    #[Assert\NotNull]
    #[Assert\NotBlank]
    private ?string $password = null;

    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $phonePrefix = null;

    #[Assert\Length(min: 2, max: 45)]
    #[Assert\Type('string')]
    private ?string $phoneNumber = null;

    #[Assert\All(constraints: [
        new Assert\Type('string', groups: ['Strict']),
        new Assert\Length('3', groups: ['Strict'])
    ], groups: ['Strict'])]
    #[Assert\Type('array')]
    private ?array $countries = null;

    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $language = null;

    #[Assert\All(constraints: [
        new Assert\Type('string', groups: ['Strict']),
    ], groups: ['Strict'])]
    #[Assert\Type('array')]
    #[Assert\NotBlank]
    private ?array $roles = null;

    #[Assert\All(constraints: [new Assert\Type('int', groups: ['Strict'])], groups: ['Strict'])]
    #[Assert\Type('array')]
    private ?array $projectIds = null;

    #[Assert\Type('boolean')]
    #[Assert\NotNull]
    private ?bool $changePassword = null;

    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    #[Assert\NotBlank]
    private string|null $firstName = null;

    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    #[Assert\NotBlank]
    private string|null $lastName = null;

    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    #[Assert\NotBlank]
    private string|null $position = null;

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string|null
     */
    public function getPhonePrefix()
    {
        return $this->phonePrefix;
    }

    /**
     * @param string|null $phonePrefix
     */
    public function setPhonePrefix($phonePrefix)
    {
        $this->phonePrefix = $phonePrefix;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @param string|null $phoneNumber
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return array|null
     */
    public function getCountries()
    {
        return $this->countries;
    }

    /**
     * @param array|null $countries
     */
    public function setCountries($countries)
    {
        $this->countries = $countries;
    }

    /**
     * @return string|null
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string|null $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * @return array|null
     */
    public function getProjectIds()
    {
        return $this->projectIds;
    }

    public function setProjectIds(?array $projectIds)
    {
        $this->projectIds = $projectIds;
    }

    /**
     * @return bool
     */
    public function isChangePassword()
    {
        return $this->changePassword;
    }

    /**
     * @param bool $changePassword
     */
    public function setChangePassword($changePassword)
    {
        $this->changePassword = $changePassword;
    }

    /**
     * @return string|null
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string|null $firstName
     */
    public function setFirstName($firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string|null
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string|null $lastName
     */
    public function setLastName($lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string|null
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param string|null $position
     */
    public function setPosition($position): void
    {
        $this->position = $position;
    }
}
