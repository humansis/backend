<?php

namespace VoucherBundle\Utils;

use CommonBundle\Entity\Logs;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use VoucherBundle\Entity\Booklet;
use Psr\Container\ContainerInterface;

class BookletService
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
   * @param Booklet $booklet
   * @param array $bookletData
   * @return mixed
   * @throws \Exception
   */
  public function create(Booklet $booklet, array $bookletData)
  {


    for ($x = 0; $x <= $bookletData['numberBooklets']; $x++) {
      // $booklet->setCode($vendorData['name']);
    };
    
    // ->setShop($vendorData['shop'])
    // ->setAddress($vendorData['address'])
    // ->setUsername($vendorData['username'])
    // ->setPassword($vendorData['password'])
    // ->setArchived(false);
    
    // $this->em->merge($vendor);
    // $this->em->flush();
    // $createdVendor = $this->em->getRepository(Vendor::class)->findOneByUsername($vendor->getUsername());
    return $booklet;
  }

  /**
   * @param Booklet $booklet
   * @param array $bookletData
   * @return Booklet
   */
  public function generateCode(Booklet $booklet, array $bookletData)
  {
    // // CREATE BOOKLET CODE #1stBatchNumber-lastBatchNumber-BookletId
    // $allBooklets = $this->em->getRepository(Booklet::class)->findAll();
    // $bookletBatch;
    // end($allBooklets);

    // if (count($allBooklets) > 1) {
    //   $bookletBatch = sprintf("%03d", $allBooklets[key($allBooklets)]->getId() + 1);
    // } elseif (count($allBooklets) == 1) {
    //   $bookletBatch = "002";
    // } elseif (!allBooklets) {
    //   $bookletBatch = "001";
    // }
    // var_dump($allBooklets);

    return $booklet;
  }

}
