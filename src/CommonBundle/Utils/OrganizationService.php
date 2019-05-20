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

}
