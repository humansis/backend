<?php

namespace CommonBundle\Utils;

use CommonBundle\Entity\Organization;
use CommonBundle\Entity\OrganizationServices;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;

class OrganizationService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * OrganizationService constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
    }

    /**
     * Returns the organization
     *
     * @return Organization
     */
    public function get()
    {
        return $this->em->getRepository(Organization::class)->findAll();
    }

    /**
     * @param Organization $organization
     * @param array $organizationArray
     * @return Organization
     * @throws \Exception
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

        $this->em->merge($organization);
        $this->em->flush();

        return $organization;
    }

    public function printTemplate()
    {
        try {
            $html = $this->container->get('templating')->render(
                '@Common/Pdf/template.html.twig',
                $this->container->get('pdf_service')->getInformationStyle()
            );

            $response = $this->container->get('pdf_service')->printPdf($html, 'portrait', 'organizationTemplate');

            return $response;
        } catch (\Exception $e) {
            throw $e;
        }

        return new Response('');
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

        $this->em->merge($organizationServices);
        $this->em->flush();

        return $organizationServices;
    }
}
