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
   * @param Booklet $booklet
   * @param array $bookletData
   * @param int $currentBatch
   * @return mixed
   * @throws \Exception
   */
  public function create(Booklet $booklet, array $bookletData, int $currentBatch, int $bookletBatch)
  {
    $code = $this->generateCode($bookletData, $currentBatch, $bookletBatch);

    $booklet->setCode($code)
      ->setNumberVouchers($bookletData['numberVouchers'])
      ->setCurrency($bookletData['currency']);

    $this->em->merge($booklet);
    $this->em->flush();
    $createdBooklet = $this->em->getRepository(Booklet::class)->findOneByCode($booklet->getCode());
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
    
    // ASSIGN EACH NUMBER TO 3 DIGITS
    // $allBooklets = $this->em->getRepository(Booklet::class)->findAll();
    // end($allBooklets);
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

}
