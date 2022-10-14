<?php

namespace Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Utils\ExportableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use FOS\UserBundle\Model\User as BaseUser;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Validator\Constraints as Assert;
use Entity\Transaction;
use Doctrine\Common\Persistence\ObjectManagerAware;

/**
 * User
 *
 * @ORM\Table(name="`user")
 * @ORM\Entity(repositoryClass="Repository\UserRepository")
 */
class User implements ExportableInterface, ObjectManagerAware, UserInterface
{
    public const ROLE_DEFAULT = 'ROLE_USER';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    /** @var ObjectManager|null */
    private $em;

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
     * @var string
     * @ORM\Column(name="password", type="string")
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
     * @Assert\NotBlank(message="Email can't be empty")
     */
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

    public function injectObjectManager(ObjectManager $objectManager, ?ClassMetadata $classMetadata = null)
    {
        $this->em = $objectManager;
    }

    /**
     * @return ObjectManager
     */
    private function getObjectManager()
    {
        if (!$this->em instanceof ObjectManager) {
            throw new RuntimeException(
                'You need to call injectObjectManager() first to use entity manager inside entity.'
            );
        }

        return $this->em;
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
     * @param UserCountry $country
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
     * @param UserCountry $country
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
     * @param UserProject $userProject
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
     * @param UserProject $userProject
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
     * @param Transaction $transaction
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
     *
     * @return array
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
     * {@inheritdoc}
     */
    public function setRoles(array $roles)
    {
        $this->roles->clear();

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addRole($roleName)
    {
        $role = $this->getObjectManager()->getRepository(Role::class)->findOneBy([
            'code' => $roleName,
        ]);

        if (!$role instanceof Role) {
            throw new InvalidArgumentException('Role with code ' . $roleName . ' does not exist.');
        }

        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeRole($roleName)
    {
        $role = $this->getObjectManager()->getRepository(Role::class)->findOneBy([
            'code' => $roleName,
        ]);

        if (!$role instanceof Role) {
            throw new InvalidArgumentException('Role with code ' . $roleName . ' does not exist.');
        }

        if (!$this->roles->contains($role)) {
            $this->roles->removeElement($role);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function getRoles()
    {
        return array_values(
            array_map(function (Role $role) {
                return $role->getCode();
            }, $this->roles->toArray())
        );
    }

//    /**
//     * {@inheritdoc}
//     */
//    public function serialize(): string
//    {
//        return serialize(array(
//            $this->password,
//            //$this->salt,
//            //$this->usernameCanonical,
//            $this->username,
//            $this->enabled,
//            $this->id,
//            $this->email,
//            //$this->emailCanonical,
//        ));
//    }
//
//    /**
//     * {@inheritdoc}
//     */
//    public function unserialize($serialized): void
//    {
//        $data = unserialize($serialized);
//
//        if (13 === count($data)) {
//            // Unserializing a User object from 1.3.x
//            unset($data[4], $data[5], $data[6], $data[9], $data[10]);
//            $data = array_values($data);
//        } elseif (11 === count($data)) {
//            // Unserializing a User from a dev version somewhere between 2.0-alpha3 and 2.0-beta1
//            unset($data[4], $data[7], $data[8]);
//            $data = array_values($data);
//        }
//
//        [
//            $this->password,
//            //$this->salt,
//            //$this->usernameCanonical,
//            $this->username,
//            $this->enabled,
//            $this->id,
//            $this->email,
//            //$this->emailCanonical
//        ] = $data;
//    }
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function eraseCredentials(): void
    {
        $this->password = null;
    }
}
