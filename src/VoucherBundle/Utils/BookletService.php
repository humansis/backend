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
use VoucherBundle\Entity\Voucher;
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
   * @return int
   */
  public function getBookletBatch()
  {
    $allBooklets = $this->em->getRepository(Booklet::class)->findAll();
    end($allBooklets); 
    if ($allBooklets) {
      $bookletBatch = $allBooklets[key($allBooklets)]->getId() + 1;
      return $bookletBatch;
    } else {
      return 0;
    }

  }

  /**
   * @param array $bookletData
   * @return mixed
   * @throws \Exception
   */
  public function create(array $bookletData)
  {
    $bookletBatch = $this->getBookletBatch();
    $currentBatch = $bookletBatch;
    $booklet;
    $createdBooklet;
    
    for ($x = 0; $x < $bookletData['numberBooklets']; $x++) {
      $booklet = new Booklet();
      $code = $this->generateCode($bookletData, $currentBatch, $bookletBatch);

      $booklet->setCode($code)
        ->setNumberVouchers($bookletData['numberVouchers'])
        ->setCurrency($bookletData['currency']);

        $this->em->merge($booklet);
        $this->em->flush();
        $currentBatch++;
        $createdBooklet = $this->em->getRepository(Booklet::class)->findOneByCode($booklet->getCode());

        $voucherData = [
          'used' => false,
          'numberVouchers' => $bookletData['numberVouchers'],
          'bookletCode' => $code,
          'currency' => $bookletData['currency'],
          'bookletID' => $createdBooklet->getId(), 
          'value' => $bookletData['voucherValue'],
        ];

        $this->container->get('voucher.voucher_service')->create($voucherData);
    }

    return $createdBooklet;
  }

  /**
   * @param array $bookletData
   * @param int $counter
   * @param int $bookletBatch
   * @return string
   */
  public function generateCode(array $bookletData, int $currentBatch, int $bookletBatch)
  {
    // CREATE BOOKLET CODE #1stBatchNumber-lastBatchNumber-BookletId
    $bookletBatchNumber;
    $lastBatchNumber = sprintf("%03d", $bookletBatch + ($bookletData['numberBooklets'] - 1));
    $currentBooklet = sprintf("%03d", $currentBatch);
    if ($bookletBatch > 1) {
      $bookletBatchNumber = sprintf("%03d", $bookletBatch);
    } elseif (!$bookletBatch) {
      $bookletBatchNumber = "000";
    }

    // GENERATES 5 RANDOM LETTERS/SYMBLES
    $rand = '';
    $seed = str_split('abcdefghijklmnopqrstuvwxyz'
    . 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
    . '0123456789');
    shuffle($seed);
    foreach (array_rand($seed, 5) as $k) $rand .= $seed[$k];
    
    // JOINS ALL PARTS, CREATING FINAL CODE
    $fullCode = $rand . '#' . $bookletBatchNumber . '-' . $lastBatchNumber . '-' . $currentBooklet;
    return $fullCode;
  }

  
  /**
   * @return string
   */
  public function findAll()
  {
    return $this->em->getRepository(Booklet::class)->findAll();
  }

  
  /**
   * @param Booklet $booklet
   * @param array $bookletData
   * @return Booklet
   */
  public function update(Booklet $booklet, array $bookletData)
  {

    foreach ($bookletData as $key => $value) {
      if ($key == 'code') {
        $booklet->setCode($value);
      } elseif ($key == 'currency') {
        $booklet->setCurrency($value);
      } elseif ($key == 'status') {
        $booklet->setStatus($value);
      } elseif ($key == 'password') {
        $booklet->setPassword($value);
      }
    }

    $this->em->merge($booklet);
    $this->em->flush();
    return $booklet;
  }


  /**
   * Perminantly delete the record from the database
   *
   * @param Booklet $booklet
   * @param bool $removeBooklet
   * @return bool
   * @throws \Exception
   */
  public function deleteBookletFromDatabase(Booklet $booklet, bool $removeBooklet = true)
  {
    $bookletId = $booklet->getId();
    $vouchers = $this->em->getRepository(Voucher::class)->findBy(['booklet' => $bookletId]);
    if ($removeBooklet && !$vouchers) {
      try {
        $this->em->remove($booklet);
        $this->em->flush();
      } catch (\Exception $exception) {
        throw new $exception('Unable to delete Booklet');
      }
    } elseif ($removeBooklet && $vouchers) {
      try {
        $this->container->get('voucher.voucher_service')->deleteBatchVouchers($booklet);
        $this->em->remove($booklet);
        $this->em->flush();
        var_dump('VOUCHERS DELETED');
      } catch (\Exception $exception) {
        throw new $exception('This booklet still contains potentially used vouchers.');
      } 
    } else {
      return false;
    }
    return true;
  }

}
