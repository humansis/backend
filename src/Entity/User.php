<?php

namespace Entity;

use Entity\Helper\StandardizedPrimaryKey;
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
use Doctrine\Common\Persistence\ObjectManagerAware;

/**
 * User
 *
 * @ORM\Table(name="`user")
 * @ORM\Entity(repositoryClass="Repository\UserRepository")
 */
class User extends BaseUser implements ExportableInterface, ObjectManagerAware
{
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
     */
    protected $password;

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
     * @Assert\NotBlank(message="Email can't be empty")
     */
    protected $email;

    /**
     * @var Collection|Role[]
     * @ORM\ManyToMany(targetEntity="Entity\Role", inversedBy="users")
     */
    protected $roles;
    
    /**
     * @var Transaction
     *
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
     * @var boolean
     * @ORM\Column(name="changePassword", type="boolean", options={"default" : 0})
     */
    protected $changePassword = false;

    /**
     * @var boolean
     * @ORM\Column(name="twoFactorAuthentication", type="boolean", options={"default" : 0})
     */
    protected $twoFactorAuthentication = false;

    public function __construct()
    {
        parent::__construct();
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
    private function getObjectManager(): ?ObjectManager
    {
        if (!$this->em instanceof ObjectManager) {
            throw new RuntimeException('You need to call injectObjectManager() first to use entity manager inside entity.');
        }

        return $this->em;
    }

    /**
     * Add country.
     *
     * @param UserCountry $country
     *
     * @return User
     */
    public function addCountry(UserCountry $country): User
    {
        $this->countries->add($country);

        return $this;
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
     * Get projects.
     *
     * @return Collection
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * Add a Transaction
     *
     * @param Transaction $transaction transaction
     *
     * @return self
     */
    public function addTransaction(Transaction $transaction): User
    {
        $this->transactions[] = $transaction;
 
        return $this;
    }

    /**
     * Returns an array representation of this class in order to prepare the export
     * @return array
     */
    public function getMappedValueForExport(): array
    {
        return [
            'email' => $this->getEmail(),
            'role' => $this->getRoles()[0],
        ];
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): User
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
    public function setVendor(Vendor $vendor = null): User
    {
        $this->vendor = $vendor;
        return $this;
    }
    /**
    * Get vendor.
    *
    * @return Vendor|null
    */
    public function getVendor(): ?Vendor
    {
        return $this->vendor;
    }

    /**
     * Set phonePrefix.
     *
     * @param string|null $phonePrefix
     *
     * @return User
     */
    public function setPhonePrefix(?string $phonePrefix): User
    {
        $this->phonePrefix = $phonePrefix;

        return $this;
    }

    /**
     * Get phonePrefix.
     *
     * @return string|null
     */
    public function getPhonePrefix(): ?string
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
    public function setPhoneNumber(?int $phoneNumber): User
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * Get phoneNumber.
     *
     * @return int|null
     */
    public function getPhoneNumber(): ?int
    {
        return $this->phoneNumber;
    }

    /**
    * Get changePassword.
    *
    * @return boolean
    */
    public function getChangePassword(): bool
    {
        return $this->changePassword;
    }

    /**
    * Set changePassword.
    *
    * @param boolean $changePassword
    *
    * @return User
    */
    public function setChangePassword(bool $changePassword): User
    {
        $this->changePassword = $changePassword;
        return $this;
    }

    /**
    * Get twoFactorAuthentication.
    *
    * @return boolean
    */
    public function getTwoFactorAuthentication(): bool
    {
        return $this->twoFactorAuthentication;
    }

    /**
    * Set twoFactorAuthentication.
    *
    * @param boolean $twoFactorAuthentication
    *
    * @return User
    */
    public function setTwoFactorAuthentication(bool $twoFactorAuthentication): User
    {
        $this->twoFactorAuthentication = $twoFactorAuthentication;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRole($roleName): bool
    {
        $role = $this->getObjectManager()->getRepository(Role::class)->findOneBy([
            'code' => $roleName,
        ]);

        if (!$role instanceof Role) {
            throw new InvalidArgumentException('Role with code '.$roleName.' does not exist.');
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
            throw new InvalidArgumentException('Role with code '.$roleName.' does not exist.');
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
            throw new InvalidArgumentException('Role with code '.$roleName.' does not exist.');
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
    public function getRoles(): array
    {
        return array_values(array_map(function (Role $role) {
            return $role->getCode();
        }, $this->roles->toArray()));
    }
}
