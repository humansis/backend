<?php

namespace VoucherBundle\Utils;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UserBundle\Entity\User;
use VoucherBundle\Builder\VoucherBuilder;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\Product;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Entity\Voucher;
use Symfony\Component\HttpFoundation\StreamedResponse;
use VoucherBundle\Entity\VoucherRecord;

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
     * @param array $vouchersData
     * @return mixed
     * @throws \Exception
     */
    public function create(array $vouchersData, $flush = true)
    {
        $builder = new VoucherBuilder($vouchersData['booklet'], $vouchersData['currency']);

        if (array_key_exists('lastId', $vouchersData)) {
            $builder->setLastId($vouchersData['lastId'] + 1);
        } else {
            $builder->setLastId($this->getLastId() + 1);
        }

        $voucher = null;
        foreach ($builder->createByValueList($vouchersData['values']) as $voucher) {
            $this->em->persist($voucher);

            if ($flush) {
                $this->em->flush();
            }
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
        // CREATE VOUCHER CODE CurrencyValue*BookletBatchNumber-lastBatchNumber-BookletId-VoucherId
        $value = $voucherData['value'];
        $currency = $voucherData['currency'];
        $booklet = $voucherData['booklet'];

        $fullCode = $currency . $value . '*' . $voucherData['bookletCode'] . '-' . $voucherId;
        $fullCode = $booklet->password ? $fullCode . '-' . $booklet->password : $fullCode;
        
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
     * @param User $scannedBy
     * @return Voucher
     * @throws \Exception
     * @deprecated Defective/incomplete processing of voucher scan
     */
    public function scannedDeprecated(array $voucherData, User $scannedBy)
    {
        try {
            $voucher = $this->em->getRepository(Voucher::class)->find($voucherData['id']);
            $vendor = $this->em->getRepository(Vendor::class)->find($voucherData['vendorId']);
            if (!$voucher || $voucher->getStatus() !== Voucher::STATE_UNASSIGNED) {
                return $voucher;
            }
            $voucher->setVendor($vendor);
            $voucher->use($scannedBy, new DateTime($voucherData['used_at'])); // TODO : check format

            foreach ($voucherData['productIds'] as $productId) {
                $product = $this->em->getRepository(Product::class)->find($productId);

                $record = VoucherRecord::create(
                    $product,
                    $voucherData['value'] ?? null,
                    $voucherData['quantity'] ?? null,
                    isset($voucherData['usedAt']) ? \DateTime::createFromFormat('d-m-Y H:i:s', $voucherData['usedAt']) : null
                );

                $voucher->addRecord($record);
            }

            $booklet = $voucher->getBooklet();
            $vouchers = $booklet->getVouchers();
            $allVouchersUsed = true;
            foreach ($vouchers as $voucher) {
                if ($voucher->getUsedAt() === null) {
                    $allVouchersUsed = false;
                }
            }
            if ($allVouchersUsed === true) {
                $booklet->setStatus(Booklet::USED);
            }

            $this->em->merge($voucher);
            $this->em->flush();
        } catch (\Exception $e) {
            throw new \Exception('Error setting Vendor or changing used status');
        }
        return $voucher;
    }

    public function redeem(Voucher $voucher, User $scannedBy): void
    {
        if ($voucher->getStatus() !== Voucher::STATE_USED) {
            throw new \InvalidArgumentException("Reddemed voucher must be used.");
        }
        $voucher->redeem($scannedBy, new DateTime());
        $this->em->persist($voucher);
        $this->em->flush();
    }

    /**
     * @param User $scannedBy
     * @param array $voucherData
     * @return Voucher
     * @throws \Exception
     */
    public function scanned(array $voucherData)
    {
        try {
            $vendor = $this->em->getRepository(Vendor::class)->find($voucherData['vendorId']);
            $product = $this->em->getRepository(Product::class)->find($voucherData['productId']);

            $record = VoucherRecord::create(
                $product,
                $voucherData['value'] ?? null,
                $voucherData['quantity'] ?? null,
                isset($voucherData['usedAt']) ? \DateTime::createFromFormat('d-m-Y H:i:s', $voucherData['usedAt']) : null
            );

            /** @var Voucher $voucher */
            $voucher = $this->em->getRepository(Voucher::class)->find($voucherData['id']);
            if (!$voucher || $voucher->getUsedAt() !== null) {
                return $voucher;
            }

            $voucher
                ->setVendor($vendor)
                ->addRecord($record);


            $booklet = $voucher->getBooklet();
            $vouchers = $booklet->getVouchers();

            $allVouchersUsed = true;
            foreach ($vouchers as $voucher) {
                if ($voucher->getUsedAt() === null) {
                    $allVouchersUsed = false;
                }
            }
            if ($allVouchersUsed === true) {
                $booklet->setStatus(Booklet::USED);
            }

            $this->em->persist($voucher);
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
     * @return bool
     * @throws \Exception
     */
    public function deleteOneFromDatabase(Voucher $voucher)
    {
        if ($voucher->getStatus() === Voucher::STATE_UNASSIGNED) {
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
        foreach ($vouchers as $value) {
            $this->deleteOneFromDatabase($value);
        };
        return true;
    }

    /**
         * Export all vouchers in a CSV file
         * @param string $type
         * @param string $countryIso3
         * @param array $ids
         * @param array $filters
         * @return mixed
         */
    public function exportToCsv(string $type, string $countryIso3, $ids, $filters)
    {
        $booklets = null;
        $maxExport = 50000;
        $limit = $type === 'csv' ? null : $maxExport;

        if ($ids) {
            $exportableTable = $this->em->getRepository(Voucher::class)->getAllByBookletIds($ids);
        } else if ($filters) {
            $booklets = $this->container->get('voucher.booklet_service')->getAll($countryIso3, $filters)[1];
        } else {
            $booklets = $this->em->getRepository(Booklet::class)->getActiveBooklets($countryIso3);
        }
        
        // If we only have the booklets, get the vouchers
        if ($booklets) {
            $exportableTable = $this->em->getRepository(Voucher::class)->getAllByBooklets($booklets);
        }

        // If csv type, return the response
        if (!$limit) {
            return $this->csvExport($exportableTable);
        }

        $total = $ids ? $this->em->getRepository(Voucher::class)->countByBookletsIds($ids) : $this->em->getRepository(Voucher::class)->countByBooklets($booklets);
        if ($total > $limit) {
            throw new \Exception("Too much vouchers for the export (".$total."). Use csv for large exports. Otherwise, for ".
            $type." export the data in batches of ".$maxExport." vouchers or less");
        }
        return $this->container->get('export_csv_service')->export($exportableTable->getResult(), 'bookletCodes', $type);
    }

    /**
     * Export all vouchers in a pdf
     * @param array $ids
     * @param string $countryIso3
     * @param array $filters
     * @return mixed
     */
    public function exportToPdf($ids, string $countryIso3, $filters)
    {
        $booklets = null;
        if ($ids) {
            $exportableTable = $this->em->getRepository(Voucher::class)->getAllByBookletIds($ids)->getResult();
        } else if ($filters) {
            $booklets = $this->container->get('voucher.booklet_service')->getAll($countryIso3, $filters)[1];
        } else {
            $booklets = $this->em->getRepository(Booklet::class)->getActiveBooklets($countryIso3);
        }

        if ($booklets) {
            $exportableTable = $this->em->getRepository(Voucher::class)->getAllByBooklets($booklets)->getResult();
        }

        try {
            $html =  $this->container->get('templating')->render(
                '@Voucher/Pdf/codes.html.twig',
                array_merge(
                    ['vouchers' => $exportableTable],
                    $this->container->get('pdf_service')->getInformationStyle()
                    )

                );

            $response = $this->container->get('pdf_service')->printPdf($html, 'portrait', 'bookletCodes');
            return $response;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    public function getLastId()
    {
        $lastVoucher = $this->em->getRepository(Voucher::class)->findBy([], ['id' => 'DESC'], 1);
        return $lastVoucher ? $lastVoucher[0]->getId() : 0;
    }


    /**
     * Remove incomplete vouchers in database
     */
    public function cleanUp()
    {
        $incompleteVoucher = $this->em->getRepository(Voucher::class)->findOneBy(['code' => '']);

        if ($incompleteVoucher) {
            $this->em->remove($incompleteVoucher);
            $this->em->flush();
        }
    }

    /**
     * Create new booklets as a background task.
     * Returns the last booklet id currently in the database and the number of booklets to create.
     *
     * @param string $country
     * @param array $bookletData
     * @return int
     */
    public function csvExport($exportableTable)
    {
        $response = new StreamedResponse(function () use ($exportableTable) {
            $data = $exportableTable->iterate();
            $csv = fopen('php://output', 'w+');
            fputcsv($csv, array('Booklet Number', 'Voucher Codes'),';');

            while (false !== ($row = $data->next())) {
                fputcsv($csv, [$row[0]->getBooklet()->getCode(), $row[0]->getCode()], ';');
                $this->em->detach($row[0]);
            }
            fclose($csv);
        });
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="bookletCodes.csv"');
        return $response;
    }
}
