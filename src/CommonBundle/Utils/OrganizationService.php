<?php

namespace CommonBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use CommonBundle\Entity\Organization;

class OrganizationService
{
    private $container;


   /** @var EntityManagerInterface $em */
   private $em;

   /**
    * OrganizationService constructor.
    * @param EntityManagerInterface $entityManager
    */
   public function __construct(EntityManagerInterface $entityManager)
   {
       $this->em = $entityManager;
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
     * @param Organization $organazation
     * @param array $organizationArray
     * @return Organization
     * @throws \Exception
     */
    public function edit(Organization $organization, array $organizationArray)
    {
       
      $organization->setName($organizationArray["name"])
        ->setLogo($organizationArray["logo"])
        ->setFont($organizationArray["font"])
        ->setPrimaryColor($organizationArray["primary_color"])
        ->setSecondaryColor($organizationArray["secondary_color"])
        ->setFooterContent($organizationArray["footer_content"]);

      $this->em->merge($organization);
      $this->em->flush();

      return $organization;
    }

}
