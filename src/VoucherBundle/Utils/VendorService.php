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
use UserBundle\Entity\User;
use JMS\Serializer\Serializer;
use Psr\Container\ContainerInterface;

class VendorService
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
     * Creates a new Vendor entity
     *
     * @param array $vendorData
     * @return mixed
     * @throws \Exception
     */
  public function create(array $vendorData)
  {
    $username = $vendorData['username'];
    $userSaved = $this->em->getRepository(User::class)->findOneByUsername($username);
    $vendorSaved = $userSaved ? $this->em->getRepository(Vendor::class)->getVendorByUser($userSaved) : null;

    if (!$vendorSaved) {
      $userSaved = $this->em->getRepository(User::class)->findOneByUsername($vendorData['username']);
      $user = $this->container->get('user.user_service')->create(
        $userSaved, 
        [
          'rights' => 'ROLE_VENDOR',
          'salt' => $vendorData['salt'],
          'password' => $vendorData['password']
        ]);

      $vendor = new Vendor();
      $vendor->setName($vendorData['name'])
      ->setShop($vendorData['shop'])
      ->setAddress($vendorData['address'])
      ->setArchived(false)
      ->setUser($user);
      
      $this->em->merge($vendor);
      $this->em->flush();

      $createdVendor = $this->em->getRepository(Vendor::class)->findOneByUser($user);
      return $createdVendor;
    } else {
      throw new \Exception('A vendor with this username already exists.');
    }
  }

  /**
   * Returns all the vendors
   *
   * @return array
   */
  public function findAll()
  {
    $vendors = $this->em->getRepository(Vendor::class)->findAll();

    foreach ($vendors as $index => $vendor) {
        if ($vendor->getArchived() === true) {
            array_splice($vendors, $index, 1);
        }
    }

    return $vendors;
  }


  /**
   * Updates a vendor according to $vendorData
   *
   * @param Vendor $vendor
   * @param array $vendorData
   * @return Vendor
   */
  public function update(Vendor $vendor, array $vendorData)
  {
    try {
      $user = $vendor->getUser();
      foreach ($vendorData as $key => $value) {
        if ($key == 'name') {
          $vendor->setName($value);
        } elseif ($key == 'shop') {
          $vendor->setShop($value);
        } elseif ($key == 'address') {
          $vendor->setAddress($value);
        } elseif ($key == 'username') {
          $user->setUsername($value);
        } elseif ($key == 'password' && !empty($value)) {
          $user->setPassword($value);
        }
      }
      $this->em->merge($vendor);
      $this->em->flush();
    } catch (\Exception $e) {
      throw new $e('Error updating Vendor');
    }

    return $vendor;
  }


  /**
   * Archives Vendor
   *
   * @param Vendor $vendor
   * @param bool $archiveVendor
   * @return Vendor
   * @throws \Exception
   */
  public function archiveVendor(Vendor $vendor, bool $archiveVendor = true)
  {
      try {
        $vendor->setArchived($archiveVendor);
        $this->em->merge($vendor);
        $this->em->flush();
      } catch (\Exception $exception) {
        throw new \Exception('Error archiving Vendor');
      }
    return $vendor;
  }


  /**
   * Permanently deletes the record from the database
   *
   * @param Vendor $vendor
   * @param bool $removeVendor
   * @return bool
   */
  public function deleteFromDatabase(Vendor $vendor, bool $removeVendor = true)
  {
    if ($removeVendor) {
      try {
        $this->em->remove($vendor);
        $this->em->flush();
      } catch (\Exception $exception) {
        return $exception;
      }
    }
    return true;
  }

  /**
     * @param User $user
     * @throws \Exception
     */
    public function login(User $user)
    {
      $vendor = $this->em->getRepository(Vendor::class)->findOneByUser($user);
      if (!$vendor) {
          throw new \Exception('You cannot log if you are not a vendor', Response::HTTP_BAD_REQUEST);
      }

        return $vendor;
    }
}
