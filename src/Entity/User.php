<?php

namespace Entity;

use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Entity\Helper\StandardizedPrimaryKey;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User
 */
#[ORM\Table(name: '`user')]
#[ORM\Entity(repositoryClass: 'Repository\UserRepository')]
class User implements UserInterface, LegacyPasswordAuthenticatedUserInterface
{
    use StandardizedPrimaryKey;

    #[Assert\NotBlank(message: "Username can't be empty")]
    #[Assert\Length(min: 2, max: 50, minMessage: 'Your username must be at least {{ limit }} characters long', maxMessage: 'Your username cannot be longer than {{ limit }} characters')]
    #[ORM\Column(name: 'username', type: 'string')]
    protected string $username;

    #[ORM\Column(name: 'password', type: 'string', nullable: false)]
    protected string $password;

    #[ORM\Column(name: 'salt', type: 'string', nullable: true)]
    protected string | null $salt;

    /**
     * @var Collection|UserCountry[]
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: 'Entity\UserCountry', cascade: ['persist', 'remove'])]
    private Collection | array $countries;

    /**
     * @var Collection|UserProject[]
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: 'Entity\UserProject', cascade: ['remove'])]
    private Collection | array $projects;

    #[Assert\NotBlank(message: "Email can't be empty")]
    #[ORM\Column(name: 'email', type: 'string')]
    protected string $email;

    #[ORM\Column(name: 'enabled', type: 'boolean')]
    protected bool $enabled;

    /**
     * @var Collection|Role[]
     */
    #[ORM\ManyToMany(targetEntity: 'Entity\Role', inversedBy: 'users')]
    protected Collection | array $roles;

    /**
     * @var Collection|Transaction[]
     */
    #[ORM\OneToMany(mappedBy: 'sentBy', targetEntity: 'Entity\Transaction')]
    private Collection | array $transactions;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: '\Entity\Vendor', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private Vendor | null $vendor;

    #[ORM\Column(name: 'language', type: 'string', length: 255, nullable: true)]
    protected string | null $language;

    #[ORM\Column(name: 'phonePrefix', type: 'string', nullable: true)]
    protected string | null $phonePrefix;

    #[ORM\Column(name: 'phoneNumber', type: 'integer', nullable: true)]
    protected int | null $phoneNumber;

    #[ORM\Column(name: 'changePassword', type: 'boolean', options: ['default' => 0])]
    protected bool $changePassword = false;

    #[ORM\Column(name: 'twoFactorAuthentication', type: 'boolean', options: ['default' => 0])]
    protected bool $twoFactorAuthentication = false;

    public function __construct(
        string $username,
        string $email,
        string $password,
        bool $enabled = false,
        string | null $salt = null,
    ) {
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->enabled = $enabled;
        $this->salt = $salt;
        $this->countries = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->roles = new ArrayCollection();
    }

    public function addCountry(UserCountry $country): User
    {
        $this->countries->add($country);

        return $this;
    }

    /**
     * @return Collection|UserCountry[]
     */
    public function getCountries(): Collection | array
    {
        return $this->countries;
    }

    /**
     * @return Collection|Project[]
     */
    public function getProjects(): Collection | array
    {
        return $this->projects;
    }

    public function addTransaction(Transaction $transaction): User
    {
        $this->transactions[] = $transaction;

        return $this;
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

    public function setVendor(Vendor $vendor = null): User
    {
        $this->vendor = $vendor;

        return $this;
    }

    public function getVendor(): ?Vendor
    {
        return $this->vendor;
    }

    public function setPhonePrefix(?string $phonePrefix): User
    {
        $this->phonePrefix = $phonePrefix;

        return $this;
    }

    public function getPhonePrefix(): ?string
    {
        return $this->phonePrefix;
    }

    public function setPhoneNumber(?int $phoneNumber): User
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getPhoneNumber(): ?int
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
        $data = $serialized;

        [
            $this->password,
            $this->username,
            $this->enabled,
            $this->id,
            $this->email,
        ] = $data;
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
