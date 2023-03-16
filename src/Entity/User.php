<?php

declare(strict_types=1);

namespace Entity;

use Repository\UserRepository;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Entity\Helper\StandardizedPrimaryKey;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, LegacyPasswordAuthenticatedUserInterface
{
    use StandardizedPrimaryKey;

    #[ORM\Column(type: 'string')]
    protected string $username;

    #[ORM\Column(type: 'string')]
    protected string $password;

    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $salt = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserCountry::class, cascade: ['persist', 'remove'])]
    private Collection $countries;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserProject::class, cascade: ['remove'])]
    private Collection $projects;

    #[ORM\Column(type: 'string')]
    protected string $email;

    #[ORM\Column(type: 'boolean')]
    protected bool $enabled;

    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    protected Collection $roles;

    #[ORM\OneToMany(mappedBy: 'sentBy', targetEntity: Transaction::class)]
    private Collection $transactions;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Vendor::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private Vendor|null $vendor = null;

    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $language = null;

    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $phonePrefix = null;

    #[ORM\Column(type: 'string', nullable: true)]
    protected string|null $phoneNumber;

    /**
     * If true, user has to change password when he logs in.
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $changePassword = false;

    #[ORM\Column(type: 'boolean')]
    protected bool $twoFactorAuthentication = false;

    #[ORM\Column(type: 'string', nullable: true)]
    private string|null $firstName = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private string|null $lastName = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private string|null $position = null;

    public function __construct()
    {
        $this->enabled = false;
        $this->countries = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    public function addCountry(UserCountry $country): void
    {
        $this->countries->add($country);
    }

    /**
     * @return Collection<UserCountry>
     */
    public function getCountries(): Collection
    {
        return $this->countries;
    }

    /**
     * @return Collection<Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addTransaction(Transaction $transaction): void
    {
        $this->transactions->add($transaction);
    }

    public function getLanguage(): string|null
    {
        return $this->language;
    }

    public function setLanguage(string|null $language): User
    {
        $this->language = $language;

        return $this;
    }

    public function setVendor(Vendor $vendor = null): User
    {
        $this->vendor = $vendor;

        return $this;
    }

    public function getVendor(): Vendor|null
    {
        return $this->vendor;
    }

    public function setPhonePrefix(string|null $phonePrefix): User
    {
        $this->phonePrefix = $phonePrefix;

        return $this;
    }

    public function getPhonePrefix(): string|null
    {
        return $this->phonePrefix;
    }

    public function setPhoneNumber(string|null $phoneNumber): User
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getPhoneNumber(): string|null
    {
        return $this->phoneNumber;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getSalt(): string|null
    {
        return $this->salt;
    }

    public function setSalt(string $salt = null): self
    {
        $this->salt = $salt;

        return $this;
    }

    public function getChangePassword(): bool
    {
        return $this->changePassword;
    }
    public function setChangePassword(bool $changePassword): User
    {
        $this->changePassword = $changePassword;

        return $this;
    }

    public function getTwoFactorAuthentication(): bool
    {
        return $this->twoFactorAuthentication;
    }

    public function setTwoFactorAuthentication(bool $twoFactorAuthentication): User
    {
        $this->twoFactorAuthentication = $twoFactorAuthentication;

        return $this;
    }

    public function hasRole(Role $role): bool
    {
        return $this->roles->contains($role);
    }

    /**
     * @param Role[] $roles
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
     */
    public function getRoles(): array
    {
        return array_values(
            array_map(fn(Role $role) => $role->getCode(), $this->roles->toArray())
        );
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @inheritDoc
     */
    public function getPassword(): string|null
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
        $data = $serialized;

        [
            $this->password,
            $this->username,
            $this->enabled,
            $this->id,
            $this->email,
        ] = $data;
    }

    public function getFirstName(): string|null
    {
        return $this->firstName;
    }

    public function setFirstName(string|null $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string|null
    {
        return $this->lastName;
    }

    public function setLastName(string|null $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getPosition(): string|null
    {
        return $this->position;
    }

    public function setPosition(string|null $position): void
    {
        $this->position = $position;
    }
}
