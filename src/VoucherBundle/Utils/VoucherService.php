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
   * @param array $voucherData
   * @return mixed
   * @throws \Exception
   */
  public function create(array $voucherData)
  {
    var_dump('ENTERS');
    $allVoucher = $this->em->getRepository(Voucher::class)->findAll();

    $id;

    if ($allVoucher) {
      end($allVoucher);
      $id = (int)$allVoucher[key($allVoucher)]->getId();
    } else {
      $id = 0;
    }

    for ($x = 0; $x < $voucherData['numberVouchers']; $x++) {
      $id++;
      $voucher = new Voucher();

      $code = $this->generateCode($voucherData, $id);
      $booklet = $this->em->getRepository(Booklet::class)->find($voucherData['bookletID']);

      $voucher->setUsed(false)
        ->setCode($code)
        ->setBooklet($booklet)
        ->setVendor(null)
        ->setIndividualValue($voucherData['value']);


      $this->em->persist($voucher);
      $this->em->flush();
      
      // end($lastId);
      $id = (int)$voucher->getId();
    }
    // $createdVendor = $this->em->getRepository(Vendor::class)->findOneByUsername($vendor->getUsername());
    return $voucher;
  }

  /**
   * @param array $voucherData
   * @param int $counter
   * @return string
   */
  public function generateCode(array $voucherData, int $counter)
  {
    // CREATE VOUCHER CODE #1stBatchNumber-lastBatchNumber-BookletId-VoucherId
    $parts = explode("#", $voucherData['bookletCode']);
    $currentVoucher = sprintf("%03d", $counter);
    $value = $voucherData['value'];
    $currency = $voucherData['currency'];

    $fullCode = $currency . $value . '#' . $parts[1] . '-' . $currentVoucher;

    return $fullCode;
  }

  /**
   * @return string
   */
  public function findAll()
  {
    return $this->em->getRepository(Voucher::class)->findAll();
  }

  /**
   * @param Voucher $voucher
   * @param array $voucherData
   * @return Voucher
   */
  public function scanned(Voucher $voucher, array $voucherData)
  {
    $vendor = $this->em->getRepository(Vendor::class)->find($voucherData['vendor']);
    $voucher->setVendor($vendor)
      ->setUsed(true);

    $this->em->merge($voucher);
    $this->em->flush();
    return $voucher;
  }

  /**
   * Perminantly delete the record from the database
   *
   * @param Voucher $voucher
   * @param bool $removeVoucher
   * @return bool
   */
  public function deleteOneFromDatabase(Voucher $voucher, bool $removeVoucher = true)
  {
    if ($removeVoucher && !$voucher->getUsed()) {
      try {
        $this->em->remove($voucher);
        $this->em->flush();
      } catch (\Exception $exception) {
        return $exception;
      }
    } else {
      var_dump('$voucher has been used, unable to delete');
    }
    return true;
  }

  /**
   * Perminantly delete the record from the database
   *
   * @param Booklet $booklet
   * @return bool
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
