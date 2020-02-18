<?php

namespace VoucherBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\Product;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Entity\Voucher;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

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
        try {
            $currentId = $this->getLastId() + 1;
            for ($x = 0; $x < $vouchersData['number_vouchers']; $x++) {
                $voucher = new Voucher();
                $voucherData = $vouchersData;
                $voucherData['value'] = $vouchersData['values'][$x];
                $booklet = $voucherData['booklet'];
                $code = $this->generateCode($voucherData, $currentId);

                $voucher->setUsedAt(null)
                        ->setCode($code)
                        ->setBooklet($booklet)
                        ->setVendor(null)
                        ->setValue($voucherData['value']);

                $currentId++;

                $this->em->persist($voucher);

                if ($flush) {
                    $this->em->flush();
                }
            }
        } catch (\Exception $e) {
            throw $e;
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
     * @return Voucher
     * @throws \Exception
     */
    public function scanned(array $voucherData)
    {
        try {
            $voucher = $this->em->getRepository(Voucher::class)->find($voucherData['id']);
            $vendor = $this->em->getRepository(Vendor::class)->find($voucherData['vendorId']);
            if (!$voucher || $voucher->getUsedAt() !== null) {
                return $voucher;
            }
            $voucher->setVendor($vendor)
                    ->setUsedAt(new \DateTime($voucherData['used_at'])); // TODO : check format

            foreach ($voucherData['productIds'] as $productId) {
                $product = $this->em->getRepository(Product::class)->find($productId);
                $voucher = $voucher->addProduct($product);
            }

            $booklet = $voucher->getBooklet();
            $vouchers = $booklet->getVouchers();
            $allVouchersUsed = true;
            foreach ($vouchers as $voucher) {
                if ($voucher->getusedAt() === null) {
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
        foreach ($vouchers as $value) {
            $this->deleteOneFromDatabase($value);
        };
        return true;
    }

    /**
         * Export all vouchers in a CSV file
         * @param string $type
         * @return mixed
         */
    public function exportToCsv(string $type, string $countryIso3, $ids, $filters)
    {
        $booklets = null;
        if ($ids) {
            $exportableTable = $this->em->getRepository(Voucher::class)->getAllByBookletIds($ids);
        } else if ($filters) {
            $booklets = $this->container->get('voucher.booklet_service')->getAll($countryIso3, $filters)[1];
        } else {
            $booklets = $this->em->getRepository(Booklet::class)->getActiveBooklets($countryIso3);
        }

        if ($booklets) {
            $exportableTable = $this->em->getRepository(Voucher::class)->getAllByBooklets($booklets);
        }
        return $this->export($exportableTable, $type);
    }

    /**
     * Export all vouchers in a pdf
     * @return mixed
     */
    public function exportToPdf($ids, $countryIso3, $filters)
    {
        $booklets = null;
        if ($ids) {
            $exportableTable = $this->em->getRepository(Voucher::class)->getAllByBookletIds($ids);
        } else if ($filters) {
            $booklets = $this->container->get('voucher.booklet_service')->getAll($countryIso3, $filters)[1];
        } else {
            $booklets = $this->em->getRepository(Booklet::class)->getActiveBooklets($countryIso3);
        }

        if ($booklets) {
            $exportableTable = [];
            foreach ($booklets as $booklet) {
                foreach ($booklet->getVouchers() as $voucher) {
                    array_push($exportableTable, $voucher);
                }
            }
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
    public function export($exportableTable, $type)
    {
        // Step 1 : Sheet construction
        $spreadsheet = new Spreadsheet();
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex(0);
        $worksheet = $spreadsheet->getActiveSheet();
        $initialIndex = 1;
        $rowIndex = $initialIndex;

        // Set headers
        $headers = ['Booklet Number', 'Voucher Codes'];
        
        $worksheet->fromArray(
            $headers,
            Null,
            'A' . $rowIndex
        );

        foreach($exportableTable as $row) {
            $rowIndex++;

            // Add a line. fromArray sets the data in the cells faster than setCellValue
            $worksheet->fromArray(
                $row->getMappedValueForExport(),
                Null,
                'A' . $rowIndex
            );

            // Used to limit the memory consumption (Can only be used with individual entities)
            $this->em->detach($row);
        }

        try {
            $filename = $this->container->get('export_csv_service')->generateFile($spreadsheet, 'bookletCodes', $type);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }

        return $filename;
    }
}
