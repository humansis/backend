<?php

namespace CommonBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use CommonBundle\Entity\Organization;

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
        $organization = $this->em->getRepository(Organization::class)->findOneBy([]);
        try {
            $html = $this->container->get('templating')->render(
            '@Common/Pdf/template.html.twig',
                array(
                  'name' => $organization->getName(),
                  'logo' => $organization->getLogo(),
                  'footer' => $organization->getFooterContent(),
                  'primaryColor' => $organization->getPrimaryColor(),
                  'secondaryColor' => $organization->getSecondaryColor(),
                  'font' => $organization->getFont(),
                )
            );

            $response = $this->container->get('pdf_service')->printPdf($html, 'organizationTemplate');

            return $response;
        } catch (\Exception $e) {
            throw $e;
        }

        return new Response('');
    }

}
