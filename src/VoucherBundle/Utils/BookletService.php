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
    $allBooklets = $this->em->getRepository(Booklet::class)->findAll();
    $bookletBatch;
    end($allBooklets);

    if (count($allBooklets) > 1) {
      $bookletBatch = sprintf("%03d", $allBooklets[key($allBooklets)]->getId() + 1);
    } elseif (count($allBooklets) == 1) {
      $bookletBatch = "002";
    } elseif (!allBooklets) {
      $bookletBatch = "001";
    }
    var_dump(gettype($bookletBatch));
    // var_dump($allBooklets);

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


  // public function findAll()
  // {
  //   return $this->em->getRepository(Vendor::class)->findAll();
  // }


  // /**
  //  * @param Vendor $vendor
  //  * @param array $vendorData
  //  * @return Vendor
  //  */
  // public function update(Vendor $vendor, array $vendorData)
  // {

  //   foreach ($vendorData as $key => $value) {
  //     if ($key == 'name') {
  //       $vendor->setName($value);
  //     } elseif ($key == 'shop') {
  //       $vendor->setShop($value);
  //     } elseif ($key == 'address') {
  //       $vendor->setAddress($value);
  //     } elseif ($key == 'username') {
  //       $vendor->setUsername($value);
  //     } elseif ($key == 'password') {
  //       $vendor->setPassword($value);
  //     }
  //   }

  //   $this->em->merge($vendor);

  //   $this->em->flush();

  //   return $vendor;
  // }

  // /**
  //  * Archive Vendor
  //  *
  //  * @param Vendor $vendor
  //  * @param bool $archiveVendor
  //  * @return bool
  //  */
  // public function archiveVendor(Vendor $vendor, bool $archiveVendor = true)
  // {
  //     try {
  //       $vendor->setArchived($archiveVendor);
  //       $this->em->merge($vendor);
  //       $this->em->flush();
  //     } catch (\Exception $exception) {
  //       return $exception;
  //     }
  //   return $vendor;
  // }

  // /**
  //  * Perminantly delete the record from the database
  //  *
  //  * @param Vendor $vendor
  //  * @param bool $removeVendor
  //  * @return bool
  //  */
  // public function deleteFromDatabase(Vendor $vendor, bool $removeVendor = true)
  // {
  //   if ($removeVendor) {
  //     try {
  //       $this->em->remove($vendor);
  //       $this->em->flush();
  //     } catch (\Exception $exception) {
  //       return $exception;
  //     }
  //   }
  //   return true;
  // }

}
