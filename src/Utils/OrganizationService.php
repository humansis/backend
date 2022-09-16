<?php

namespace Utils;

use Entity\Organization;
use Entity\OrganizationServices;
use Doctrine\ORM\EntityManagerInterface;
use InputType\OrganizationUpdateInputType;

class OrganizationService
{
    /** @var EntityManagerInterface $em */
    private $em;
    /**
     * OrganizationService constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param Organization $organization
     * @param OrganizationUpdateInputType $inputType
     */
    public function update(Organization $organization, OrganizationUpdateInputType $inputType)
    {
        $organization
            ->setName($inputType->getName())
            ->setFont($inputType->getFont())
            ->setPrimaryColor($inputType->getPrimaryColor())
            ->setSecondaryColor($inputType->getSecondaryColor())
            ->setFooterContent($inputType->getFooterContent())
            ->setLogo($inputType->getLogo());

        $this->em->persist($organization);
        $this->em->flush();
    }

    public function setEnable(OrganizationServices $organizationServices, bool $enabled)
    {
        $organizationServices->setEnabled($enabled);
        $this->em->flush();
    }

    public function setParameters(OrganizationServices $organizationServices, $json)
    {
        if (false === $json || [] !== array_diff(array_keys($organizationServices->getParametersValue()), array_keys($json))) {
            throw new \RuntimeException('Unable to save organization service parameters. Invalid JSON given.');
        }

        $organizationServices->setParametersValue($json);
        $this->em->flush();
    }
}
