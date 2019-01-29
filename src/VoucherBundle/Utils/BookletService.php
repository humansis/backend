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

  // =============== GETS BOOKLET BATCH ===============
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


  // =============== CREATES BOOKLET ===============
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

      // === creates booklet ===
      try {
        $booklet = new Booklet();
        $code = $this->generateCode($bookletData, $currentBatch, $bookletBatch);
  
        $booklet->setCode($code)
          ->setNumberVouchers($bookletData['numberVouchers'])
          ->setCurrency($bookletData['currency']);
  
        $this->em->merge($booklet);
        $this->em->flush();
  
        $currentBatch++;
        $createdBooklet = $this->em->getRepository(Booklet::class)->findOneByCode($booklet->getCode());
      } catch (\Exception $e) {
        throw new $e('Error creating Booklet');
      }

      //=== creates vouchers ===
      try {
        $voucherData = [
          'used' => false,
          'numberVouchers' => $bookletData['numberVouchers'],
          'bookletCode' => $code,
          'currency' => $bookletData['currency'],
          'bookletID' => $createdBooklet->getId(),
          'value' => $bookletData['voucherValue'],
        ];
  
        $this->container->get('voucher.voucher_service')->create($voucherData);
      } catch (\Exception $e) {
        throw new $e('Error creating vouchers');
      }
    }

    return $createdBooklet;
  }


  // =============== GENERATES BOOKLET CODE ===============
  /**
   * @param array $bookletData
   * @param int $currentBatch
   * @param int $bookletBatch
   * @return string
   */
  public function generateCode(array $bookletData, int $currentBatch, int $bookletBatch)
  {
    // === randomCode#bookletBatchNumber-lastBatchNumber-currentBooklet ===
    $bookletBatchNumber;
    $lastBatchNumber = sprintf("%03d", $bookletBatch + ($bookletData['numberBooklets'] - 1));
    $currentBooklet = sprintf("%03d", $currentBatch);

    if ($bookletBatch > 1) {
      $bookletBatchNumber = sprintf("%03d", $bookletBatch);
    } elseif (!$bookletBatch) {
      $bookletBatchNumber = "000";
    }

    // === generates randomCode before # ===
    $rand = '';
    $seed = str_split('abcdefghijklmnopqrstuvwxyz'
      . 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
      . '0123456789');
    shuffle($seed);
    foreach (array_rand($seed, 5) as $k) $rand .= $seed[$k];
    
    // === joins all parts together ===
    $fullCode = $rand . '#' . $bookletBatchNumber . '-' . $lastBatchNumber . '-' . $currentBooklet;
    return $fullCode;
  }

  
  // =============== RETURNS ALL BOOKLETS ===============
  /**
   * @return array
   */
  public function findAll()
  {
    return $this->em->getRepository(Booklet::class)->findAll();
  }


  // =============== UPDATE BOOKLET ===============
  /**
   * @param Booklet $booklet
   * @param array $bookletData
   * @return Booklet
   * @throws \Exception
   */
  public function update(Booklet $booklet, array $bookletData)
  {

    try {
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
    } catch (\Exception $e) {
      throw new $e('Error updating Booklet');
    }
    return $booklet;
  }


  // =============== DELETE 1 BOOKLET AND ITS VOUCHERS FROM DATABASE ===============
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
    // === check if booklet has any vouchers ===
    $bookletId = $booklet->getId();
    $vouchers = $this->em->getRepository(Voucher::class)->findBy(['booklet' => $bookletId]);
    if ($removeBooklet && !$vouchers) {
      try {
        // === if no vouchers then delete ===
        $this->em->remove($booklet);
        $this->em->flush();
      } catch (\Exception $exception) {
        throw new $exception('Unable to delete Booklet');
      }
    } 
    elseif ($removeBooklet && $vouchers) {
      try {
        // === if there are vouchers then delete those that are not used ===
        $this->container->get('voucher.voucher_service')->deleteBatchVouchers($booklet);
        $this->em->remove($booklet);
        $this->em->flush();
      } catch (\Exception $exception) {
        throw new $exception('This booklet still contains potentially used vouchers.');
      }
    } 
    else {
      return false;
    }
    return true;
  }

}
