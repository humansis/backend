<?php

namespace Entity;

use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Utils\ExportableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User
 *
 * @ORM\Table(name="`user")
 * @ORM\Entity(repositoryClass="Repository\UserRepository")
 */
class User implements ExportableInterface, UserInterface, LegacyPasswordAuthenticatedUserInterface
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="username", type="string")
     */
    #[Assert\NotBlank(message: "Username can't be empty")]
    #[Assert\Length(min: 2, max: 50, minMessage: 'Your username must be at least {{ limit }} characters long', maxMessage: 'Your username cannot be longer than {{ limit }} characters')]
    protected $username;

    /**
     * @var string
     * @ORM\Column(name="password", type="string", nullable=false)
     */
    protected $password;

    /**
     * The salt to use for hashing.
     *
     * @var string|null
     * @ORM\Column(name="salt", type="string", nullable=true)
     */
    protected $salt;

    /**
     * @ORM\OneToMany(targetEntity="Entity\UserCountry", mappedBy="user", cascade={"persist","remove"})
     */
    private $countries;

    /**
     * @ORM\OneToMany(targetEntity="Entity\UserProject", mappedBy="user", cascade={"remove"})
     */
    private $projects;

    /**
     * @var string
     * @ORM\Column(name="email", type="string")
     */
    #[Assert\NotBlank(message: "Email can't be empty")]
    protected $email;

    /**
     * @var bool
     * @ORM\Column(name="enabled", type="boolean")
     */
    protected $enabled;

    /**
     * @var Collection|Role[]
     * @ORM\ManyToMany(targetEntity="Entity\Role", inversedBy="users")
     */
    protected $roles;

    /**
     * @var Transaction
     * @ORM\OneToMany(targetEntity="Entity\Transaction", mappedBy="sentBy")
     */
    private $transactions;

    /**
     * @ORM\OneToOne(targetEntity="\Entity\Vendor", mappedBy="user", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    private $vendor;

    /**
     * @var string
     * @ORM\Column(name="language", type="string", length=255, nullable=true)
     */
    protected $language;

    /**
     * @var string
     *
     * @ORM\Column(name="phonePrefix", type="string", nullable=true)
     */
    protected $phonePrefix;

    /**
     * @var int|null
     *
     * @ORM\Column(name="phoneNumber", type="integer", nullable=true)
     */
    protected $phoneNumber;

    /**
     * @var bool
     * @ORM\Column(name="changePassword", type="boolean", options={"default" : 0})
     */
    protected $changePassword = false;

    /**
     * @var bool
     * @ORM\Column(name="twoFactorAuthentication", type="boolean", options={"default" : 0})
     */
    protected $twoFactorAuthentication = false;

    public function __construct()
    {
        $this->enabled = false;
        $this->countries = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->roles = new ArrayCollection();
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
     *
     * @return User
     */
    public function addCountry(UserCountry $country)
    {
        $this->countries->add($country);

        return $this;
    }

    /**
     * Remove country.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCountry(UserCountry $country)
    {
        return $this->countries->removeElement($country);
    }

    /**
     * Get countries.
     *
     * @return Collection
     */
    public function getCountries()
    {
        return $this->countries;
    }

    /**
     * Add userProject.
     *
     *
     * @return User
     */
    public function addUserProject(UserProject $userProject)
    {
        $this->projects[] = $userProject;

        return $this;
    }

    /**
     * Remove userProject.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeUserProject(UserProject $userProject)
    {
        return $this->projects->removeElement($userProject);
    }

    /**
     * Get projects.
     *
     * @return Collection
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * Get the value of Transaction
     *
     * @return Transaction
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * Add a Transaction
     *
     * @param Transaction transaction
     *
     * @return self
     */
    public function addTransaction(Transaction $transaction)
    {
        $this->transactions[] = $transaction;

        return $this;
    }

    /**
     * Remove a Transaction
     *
     * @return self
     */
    public function removeTransaction(Transaction $transaction)
    {
        $this->transactions->removeElement($transaction);

        return $this;
    }

    /**
     * Set transactions
     *
     * @param $collection
     *
     * @return self
     */
    public function setPhones(Collection $collection = null)
    {
        $this->transactions = $collection;

        return $this;
    }

    /**
     * Returns an array representation of this class in order to prepare the export
     */
    public function getMappedValueForExport(): array
    {
        return [
            'email' => $this->getEmail(),
            'role' => $this->getRoles()[0],
        ];
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Set vendor.
     *
     * @param Vendor|null $vendor
     *
     * @return User
     */
    public function setVendor(Vendor $vendor = null)
    {
        $this->vendor = $vendor;

        return $this;
    }

    /**
     * Get vendor.
     *
     * @return Vendor|null
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * Set phonePrefix.
     *
     * @param string $phonePrefix
     *
     * @return User
     */
    public function setPhonePrefix($phonePrefix)
    {
        $this->phonePrefix = $phonePrefix;

        return $this;
    }

    /**
     * Get phonePrefix.
     *
     * @return string
     */
    public function getPhonePrefix()
    {
        return $this->phonePrefix;
    }

    /**
     * Set phoneNumber.
     *
     * @param int|null $phoneNumber
     *
     * @return User
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * Get phoneNumber.
     *
     * @return int|null
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled($boolean): self
    {
        $this->enabled = (bool) $boolean;

        return $this;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string|null The encoded password if any
     */
    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function setSalt(string $salt = null): self
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get changePassword.
     *
     * @return bool
     */
    public function getChangePassword()
    {
        return $this->changePassword;
    }

    /**
     * Set changePassword.
     *
     * @param bool $changePassword
     *
     * @return User
     */
    public function setChangePassword($changePassword)
    {
        $this->changePassword = $changePassword;

        return $this;
    }

    /**
     * Get twoFactorAuthentication.
     *
     * @return bool
     */
    public function getTwoFactorAuthentication()
    {
        return $this->twoFactorAuthentication;
    }

    /**
     * Set twoFactorAuthentication.
     *
     * @param bool $twoFactorAuthentication
     *
     * @return User
     */
    public function setTwoFactorAuthentication($twoFactorAuthentication)
    {
        $this->twoFactorAuthentication = $twoFactorAuthentication;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRole($roleName)
    {
        $role = $this->getObjectManager()->getRepository(Role::class)->findOneBy([
            'code' => $roleName,
        ]);

        if (!$role instanceof Role) {
            throw new InvalidArgumentException('Role with code ' . $roleName . ' does not exist.');
        }

        return $this->roles->contains($role);
    }

    /**
     * @param Role[] $roles
     * @return $this
     */
    public function setRoles(array $roles): self
    {
        $this->roles->clear();

        foreach ($roles as $role) {
            $this->roles->add($role);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function getRoles(): array
    {
        return array_values(
            array_map(fn(Role $role) => $role->getCode(), $this->roles->toArray())
        );
    }


    public function __serialize(): array
    {
        return [
            $this->password,
            $this->username,
            $this->enabled,
            $this->id,
            $this->email,
        ];
    }

    public function __unserialize($serialized): void
    {
        $data = unserialize($serialized);

        [
            $this->password,
            $this->username,
            $this->enabled,
            $this->id,
            $this->email,
        ] = $data;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user (like a password in plaintext), clear it here
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }
}
