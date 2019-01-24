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
   * @param Voucher $voucher
   * @param array $voucherData
   * @return mixed
   * @throws \Exception
   */
  public function create(Voucher $voucher, array $voucherData)
  {
    $counter = 0;
    for ($x = 0; $x <= $numberVouchers; $x++) {
      $code = $this->generateCode($voucherData, $counter);
      $counter++;
      var_dump($code);
    };
    
    
    // $this->em->merge($vendor);
    // $this->em->flush();
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
    $bookletBatch = sprintf("%03d", $voucherData['bookletBatch']);
    $currentBooklet = sprintf("%03d", $voucherData['currentBatch']);
    $lastBatchNumber = sprintf("%03d", $voucherData['bookletBatch'] + $voucherData['numberBooklets']);
    $individualValue = $voucherData['value'];
    $count = sprintf("%03d", $counter);

    return '#' . $voucherData['currency'] . $individualValue . '#' . $bookletBatch . '-' . $lastBatchNumber . '-' . $currentBooklet . '-' . $count;
  }

}
