<?php

namespace CommonBundle\Utils;

use CommonBundle\Entity\Organization;
use CommonBundle\Entity\OrganizationServices;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\InputType\OrganizationUpdateInputType;
use Psr\Container\ContainerInterface;
use Twig\Environment;
use UserBundle\Entity\User;

class OrganizationService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * OrganizationService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface     $container
     * @param Environment            $environment
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container, Environment $environment)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->twig = $environment;
    }

    /**
     * Returns the organization
     *
     * @return Organization[]
     */
    public function get(): iterable
    {
        return $this->em->getRepository(Organization::class)->findAll();
    }

    /**
     * @param Organization $organization
     * @param array $organizationArray
     * @return Organization
     * @throws \Exception
     *
     * @deprecated
     */
    public function edit(Organization $organization, array $organizationArray)
    {
        $organization->setName($organizationArray["name"])
            ->setFont($organizationArray["font"])
            ->setPrimaryColor($organizationArray["primary_color"])
            ->setSecondaryColor($organizationArray["secondary_color"])
            ->setFooterContent($organizationArray["footer_content"]);

        if (array_key_exists('logo', $organizationArray)) {
            $organization->setLogo($organizationArray["logo"]);
        }

        $this->em->persist($organization);
        $this->em->flush();

        return $organization;
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

    public function printTemplate()
    {
        try {
            $html = $this->twig->render(
                '@Common/Pdf/template.html.twig',
                $this->container->get('pdf_service')->getInformationStyle()
            );

            $response = $this->container->get('pdf_service')->printPdf($html, 'portrait', 'organizationTemplate');

            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getOrganizationServices(Organization $organization)
    {
        $organizationServices = $this->em->getRepository(OrganizationServices::class)->findBy(["organization" => $organization]);

        return $organizationServices;
    }

    public function editOrganizationServices(OrganizationServices $organizationServices, array $data)
    {
        $organizationServices->setEnabled($data["enabled"])
            ->setParametersValue($data["parameters"]);

        $this->toggleService($organizationServices, $data["enabled"]);
        $this->em->persist($organizationServices);
        $this->em->flush();

        return $organizationServices;
    }

    private function toggleService(OrganizationServices $organizationServices, bool $enabled)
    {
        if ($organizationServices->getService()->getName() === "Two Factor Authentication") {
            $this->em->getRepository(User::class)->toggleTwoFA($enabled);
        }
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
