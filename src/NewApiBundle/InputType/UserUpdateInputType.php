<?php

namespace NewApiBundle\InputType;

use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"UserUpdateInputType", "Strict"})
 */
class UserUpdateInputType implements InputTypeInterface
{
    /**
     * @var string $username
     *
     * @Assert\Length(min="2", max="45")
     * @Assert\Type("string")
     * @Assert\NotNull
     * @Assert\NotBlank
     */
    private $username;

    /**
     * @var string $email
     *
     * @Assert\Length(max="180")
     * @Assert\Type("string")
     * @Assert\NotNull
     * @Assert\NotBlank
     */
    private $email;

    /**
     * @var string|null $password
     *
     * @Assert\Length(max="255")
     * @Assert\Type("string")
     */
    private $password;

    /**
     * @var string|null $phonePrefix
     *
     * @Assert\Length(max="255")
     * @Assert\Type("string")
     */
    private $phonePrefix;

    /**
     * @var string|null $phoneNumber
     *
     * @Assert\Length(min="2", max="45")
     * @Assert\Type("string")
     */
    private $phoneNumber;

    /**
     * @var array|null $countries
     *
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("string", groups={"Strict"}),
     *         @Assert\Length ("3", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    private $countries;

    /**
     * @var string|null $language
     *
     * @Assert\Length(max="255")
     * @Assert\Type("string")
     */
    private $language;

    /**
     * @var array $roles
     *
     * @Assert\Type("array")
     * @Assert\NotBlank
     * @Assert\NotNull
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("string", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    private $roles;

    /**
     * @var array|null $projectIds
     *
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("int", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    private $projectIds;

    /**
     * @var boolean $changePassword
     *
     * @Assert\Type("boolean")
     * @Assert\NotNull
     */
    private $changePassword;

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

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
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string|null $password
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

    /**
     * @param array|null $projectIds
     */
    public function setProjectIds(?array $projectIds)
    {
        $this->projectIds = $projectIds;
    }

    /**
     * @return bool
     */
    public function isChangePassword(): bool
    {
        return $this->changePassword;
    }

    /**
     * @param bool $changePassword
     */
    public function setChangePassword(bool $changePassword): void
    {
        $this->changePassword = $changePassword;
    }

}
