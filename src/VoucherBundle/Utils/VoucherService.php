<?php

namespace VoucherBundle\Utils;

use CommonBundle\Entity\Logs;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use VoucherBundle\Entity\Vendor;
use Psr\Container\ContainerInterface;

class VoucherService
{

  /** @var EntityManagerInterface $em */
  private $em;

  /** @var ValidatorInterface $validator */
  private $validator;

  /** @var ContainerInterface $container */
  private $container;

  /**
   * UserService constructor.
   * @param EntityManagerInterface $entityManager
   * @param ValidatorInterface $validator
   * @param ContainerInterface $container
   */
  public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, ContainerInterface $container)
  {
    $this->em = $entityManager;
    $this->validator = $validator;
    $this->container = $container;
  }

  /**
   * @param Voucher $vendor
   * @param array $vendorData
   * @return mixed
   * @throws \Exception
   */
  public function create(Vendor $vendor, array $vendorData)
  {
    $vendorSaved = $this->em->getRepository(Vendor::class)->findOneByUsername($vendor->getUsername());
    if (!$vendorSaved) {
      $vendor->setName($vendorData['name'])
        ->setShop($vendorData['shop'])
        ->setAddress($vendorData['address'])
        ->setUsername($vendorData['username'])
        ->setPassword($vendorData['password']);
    }

    $this->em->merge($vendor);

    $this->em->flush();
    return $vendor;
  }

  public function findAll()
  {
    return $this->em->getRepository(Vendor::class)->findAll();
  }
}
