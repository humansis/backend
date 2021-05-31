<?php

namespace NewApiBundle\Mapper;

use NewApiBundle\Component\Country\Countries;
use NewApiBundle\Component\Country\Country;
use NewApiBundle\Serializer\MapperInterface;
use ProjectBundle\Entity\Project;
use ProjectBundle\Repository\ProjectRepository;
use UserBundle\Entity\User;
use UserBundle\Entity\UserCountry;
use UserBundle\Entity\UserProject;

class UserMapper implements MapperInterface
{
    /** @var User */
    private $object;

    /** @var Countries */
    private $countries;

    /** @var ProjectRepository */
    private $projectRepository;

    public function __construct(Countries $countries, ProjectRepository $projectRepository)
    {
        $this->countries = $countries;
        $this->projectRepository = $projectRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof User && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof User) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.User::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getUsername(): string
    {
        return $this->object->getUsername();
    }

    public function getEmail(): string
    {
        return $this->object->getEmail();
    }

    public function getPhonePrefix(): ?string
    {
        return $this->object->getPhonePrefix();
    }

    public function getPhoneNumber(): ?string
    {
        return (string) $this->object->getPhoneNumber();
    }

    public function getCountries(): array
    {
        return array_map(function (UserCountry $item) {
            return $item->getIso3();
        }, $this->object->getCountries()->toArray());
    }

    public function getLanguage(): ?string
    {
        return $this->object->getLanguage();
    }

    public function getRoles(): array
    {
        return $this->object->getRoles();
    }

    public function getProjectIds(): array
    {
        // user without related projects should have access to all projects
        if ($this->object->getProjects()->isEmpty()) {
            return array_map(function (Project $item) {
                return $item->getId();
            }, $this->projectRepository->findByCountries($this->getCountries()));
        }

        return array_map(function (UserProject $item) {
            return $item->getProject()->getId();
        }, $this->object->getProjects()->toArray());
    }

    public function getChangePassword(): bool
    {
        return $this->object->getChangePassword();
    }

    public function get2fa(): bool
    {
        return $this->object->getTwoFactorAuthentication();
    }
}
