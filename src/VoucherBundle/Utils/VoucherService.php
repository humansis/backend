<?php

namespace VoucherBundle\Utils;

use CommonBundle\Entity\Logs;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use VoucherBundle\Entity\Voucher;
use Psr\Container\ContainerInterface;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Entity\Product;

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
   * Creates a new Voucher entity
   *
   * @param array $voucherData
   * @return mixed
   * @throws \Exception
   */
  public function create(array $voucherData)
  {
    try {
      $allVoucher = $this->em->getRepository(Voucher::class)->findAll();
      if ($allVoucher) {
        end($allVoucher);
        $id = (int)$allVoucher[key($allVoucher)]->getId();
      } else {
        $id = 0;
      }
    } catch (\Exception $e) {
      throw new \Exception('Error finding last voucher id');
    }

    try {
      for ($x = 0; $x < $voucherData['number_vouchers']; $x++) {
        $id++;
        $voucher = new Voucher();
  
        $code = $this->generateCode($voucherData, $id);
        $booklet = $this->em->getRepository(Booklet::class)->find($voucherData['bookletID']);

        $voucher->setUsedAt(null)
          ->setCode($code)
          ->setBooklet($booklet)
          ->setVendor(null)
          ->setValue($voucherData['value']);

        $this->em->persist($voucher);
        $this->em->flush();

        $id = (int)$voucher->getId();
      }
    } catch (\Exception $e) {
      throw new \Exception('Error creating voucher');
    }

    return $voucher;
  }


  /**
   * Generate a new random code for a voucher
   *
   * @param array $voucherData
   * @param int $voucherId
   * @return string
   */
  public function generateCode(array $voucherData, int $voucherId)
  {
    // CREATE VOUCHER CODE #BookletBatchNumber-lastBatchNumber-BookletId-VoucherId
    $parts = explode("#", $voucherData['bookletCode']);
    $currentVoucher = sprintf("%03d", $voucherId);
    $value = $voucherData['value'];
    $currency = $voucherData['currency'];

    $fullCode = $currency . $value . '#' . $parts[1] . '-' . $currentVoucher;
    return $fullCode;
  }


  /**
   * Returns all the vouchers
   *
   * @return array
   */
  public function findAll()
  {
    return $this->em->getRepository(Voucher::class)->findAll();
  }


  /**
   * @param array $voucherData
   * @return Voucher
   * @throws \Exception
   */
  public function scanned(array $voucherData)
  {
    try {
      $voucher = $this->em->getRepository(Voucher::class)->find($voucherData['id']);
      $vendor = $this->em->getRepository(Vendor::class)->find($voucherData['vendorId']);
      $voucher->setVendor($vendor)
        ->setUsedAt(new \DateTime($voucherData['used_at']));

      foreach ($voucherData['productIds'] as $productId) {
        $product = $this->em->getRepository(Product::class)->find($productId);
        $voucher = $voucher->addProduct($product);
      }
  
      $this->em->merge($voucher);
      $this->em->flush();

    } catch (\Exception $e) {
      throw new \Exception('Error setting Vendor or changing used status');
    }
    return $voucher;
  }

  /**
   * Deletes a voucher from the database
   *
   * @param Voucher $voucher
   * @param bool $removeVoucher
   * @return bool
   * @throws \Exception
   */
  public function deleteOneFromDatabase(Voucher $voucher, bool $removeVoucher = true)
  {
    if ($removeVoucher && $voucher->getUsedAt() === null) {
        $this->em->remove($voucher);
        $this->em->flush();
    } else {
      throw new \Exception('$voucher has been used, unable to delete');
    }
    return true;
  }

  // =============== DELETE A BATCH OF VOUCHERS ===============
  /**
   * Deletes all the vouchers of the given booklet
   *
   * @param Booklet $booklet
   * @return bool
   * @throws \Exception
   */
  public function deleteBatchVouchers(Booklet $booklet)
  {
    $bookletId = $booklet->getId();
    $vouchers = $this->em->getRepository(Voucher::class)->findBy(['booklet' => $bookletId]);
    foreach($vouchers as $value) {
      $this->deleteOneFromDatabase($value);
    };
    return true;
  }

}
