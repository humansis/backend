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
   * @param int $counter
   * @return mixed
   * @throws \Exception
   */
  public function create(array $voucherData)
  {
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
      $booklet = $this->em->getRepository(Booklet::class)->find(143);

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

  

}
