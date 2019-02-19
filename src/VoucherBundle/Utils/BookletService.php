<?php

namespace VoucherBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Entity\Logs;
use DistributionBundle\Entity\DistributionBeneficiary;
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
   * Returns the index of the next booklet to be inserted in the database
   *
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
   * Creates a new Booklet entity
   *
   * @param array $bookletData
   * @return mixed
   * @throws \Exception
   */
  public function create(array $bookletData)
  {
    $bookletBatch = $this->getBookletBatch();
    $currentBatch = $bookletBatch;

    for ($x = 0; $x < $bookletData['number_booklets']; $x++) {

      // === creates booklet ===
      try {
        $booklet = new Booklet();
        $code = $this->generateCode($bookletData, $currentBatch, $bookletBatch);

        $booklet->setCode($code)
          ->setNumberVouchers($bookletData['number_vouchers'])
          ->setCurrency($bookletData['currency'])
          ->setStatus(0)
          ->setArchived(0);

        $this->em->merge($booklet);
        $this->em->flush();

        $currentBatch++;
        $createdBooklet = $this->em->getRepository(Booklet::class)->findOneByCode($booklet->getCode());
      } catch (\Exception $e) {
        throw new \Exception('Error creating Booklet ' . $e->getMessage() . ' ' . $e->getLine());
      }

      //=== creates vouchers ===
      try {
        $voucherData = [
          'number_vouchers' => $bookletData['number_vouchers'],
          'bookletCode' => $code,
          'currency' => $bookletData['currency'],
          'bookletID' => $createdBooklet->getId(),
          'value' => $bookletData['individual_value'],
        ];
  
        $this->container->get('voucher.voucher_service')->create($voucherData);
      } catch (\Exception $e) {
        throw new \Exception('Error creating vouchers');
      }
    }

    return $createdBooklet;
  }


  /**
   * Generates a random code for a booklet
   *
   * @param array $bookletData
   * @param int $currentBatch
   * @param int $bookletBatch
   * @return string
   */
  public function generateCode(array $bookletData, int $currentBatch, int $bookletBatch)
  {
    // === randomCode#bookletBatchNumber-lastBatchNumber-currentBooklet ===
    $lastBatchNumber = sprintf("%03d", $bookletBatch + ($bookletData['number_booklets'] - 1));
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

  
  /**
   * Get all the non-archived booklets from the database
   *
   * @return array
   */
  public function findAll()
  {
    return  $this->em->getRepository(Booklet::class)->findBy(['archived' => false]);
  }

  /**
   * Get all the archived booklets from the database
   *
   * @return array
   */
  public function findDeactivated()
  {
    return  $this->em->getRepository(Booklet::class)->findBy(['archived' => true]);
  }



  /**
   * Updates a booklet
   *
   * @param Booklet $booklet
   * @param array $bookletData
   * @return Booklet
   * @throws \Exception
   */
  public function update(Booklet $booklet, array $bookletData)
  {

    try {

        $booklet->setCurrency($bookletData['currency']);
        $this->em->merge($booklet);

        $vouchers = $this->em->getRepository(Voucher::class)->findBy(['booklet' => $booklet->getId()]);
        /** @var $voucher Voucher */
        foreach ($vouchers as $voucher) {
            $voucher->setValue($bookletData['individual_value']);
            $this->em->merge($voucher);
        }

        $this->em->flush();

    } catch (\Exception $e) {
      throw new \Exception('Error updating Booklet');
    }
    return $booklet;
  }


    /**
     * Archive a booklet
     *
     * @param Booklet $booklet
     * @return string
     */
    public function archive(Booklet $booklet) {
        $booklet->setArchived(true);

        $this->em->merge($booklet);
        $this->em->flush();

        return "Booklet has been archived";
    }

    /**
     * Archive many booklet
     *
     * @param int[] $bookletIds
     * @return string
     */
    public function archiveMany(?array $bookletIds = [])
    {
      foreach ($bookletIds as $bookletId) {
        $booklet = $this->em->getRepository(Booklet::class)->find($bookletId);
        $booklet->setArchived(true);
        $this->em->merge($booklet);
      }
      
      $this->em->flush();

      return "Booklets have been archived";
    }


    /**
     * Update the password of the booklet
     *
     * @param Booklet $booklet
     * @param int $code
     * @throws \Exception
     *
     * @return string
     */
    public function updatePassword(Booklet $booklet, $password) {
        if ($booklet->getArchived()){
            throw new \Exception("This booklet has already been used and is actually archived");
        }

        $booklet->setPassword($password);
        $this->em->merge($booklet);
        $this->em->flush();

        return "Password has been set";
    }

    /**
     * Assign the booklet to a beneficiary
     *
     * @param Booklet $booklet
     * @param Beneficiary $beneficiary
     * @throws \Exception
     *
     * @return string
     */
    public function assign(Booklet $booklet, Beneficiary $beneficiary) {
        if ($booklet->getArchived()){
            throw new \Exception("This booklet has already been used and is actually archived");
        }

        $distributionBeneficiary = $this->em->getRepository(DistributionBeneficiary::class)->findOneByBeneficiary($beneficiary);

        $booklet->setDistributionBeneficiary($distributionBeneficiary);
        $this->em->merge($booklet);
        $this->em->flush();

        return "Booklet successfully assigned to the beneficiary";
    }

  // =============== DELETE 1 BOOKLET AND ITS VOUCHERS FROM DATABASE ===============
  /**
   * Permanently delete the record from the database
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
        throw new \Exception('Unable to delete Booklet');
      }
    } 
    elseif ($removeBooklet && $vouchers) {
      try {
        // === if there are vouchers then delete those that are not used ===
        $this->container->get('voucher.voucher_service')->deleteBatchVouchers($booklet);
        $this->em->remove($booklet);
        $this->em->flush();
      } catch (\Exception $exception) {
        throw new \Exception('This booklet still contains potentially used vouchers.');
      }
    } 
    else {
      return false;
    }
    return true;
  }

}
