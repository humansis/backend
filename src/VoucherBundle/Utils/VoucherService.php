<?php

namespace VoucherBundle\Utils;


use CommonBundle\InputType\Country;
use CommonBundle\InputType\DataTableType;
use CommonBundle\InputType\RequestConverter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UserBundle\Entity\User;
use VoucherBundle\DTO\RedemptionVoucherBatchCheck;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\Product;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Entity\Voucher;
use Symfony\Component\HttpFoundation\StreamedResponse;
use VoucherBundle\Entity\VoucherRecord;
use VoucherBundle\InputType\VoucherRedemptionBatch;

class VoucherService
{

  /** @var EntityManagerInterface $em */
    private $em;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var int */
    private $exportLimit;
    /** @var int */
    private $exportLimitCSV;

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
        $this->exportLimit = $container->getParameter('app.export.limit');
        $this->exportLimitCSV = $container->getParameter('app.export.limit_csv');
    }

    /**
     * Creates a new Voucher entity
     *
     * @param array $vouchersData
     * @return array
     * @throws \Exception
     */
    public function create(array $vouchersData, $flush = true)
    {
        $vouchers = [];
        try {
            $currentId = array_key_exists('lastId', $vouchersData) ? $vouchersData['lastId'] + 1 : $this->getLastId() + 1;
            for ($x = 0; $x < $vouchersData['number_vouchers']; $x++) {

                $voucherData = $vouchersData;
                $voucherData['value'] = $vouchersData['values'][$x];
                /** @var Booklet $booklet */
                $booklet = $voucherData['booklet'];
                $code = $this->generateCode($voucherData, $currentId);

                $vouchers[] = $voucher = new Voucher($code, $voucherData['value'], $booklet);
                $booklet->getVouchers()->add($voucher);
                $currentId++;

                $this->em->persist($voucher);

                if ($flush) {
                    $this->em->flush();
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
        return $vouchers;
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
     * @param VoucherRedemptionBatch $batch
     *
     * @param Vendor|null            $vendor
     *
     * @return RedemptionVoucherBatchCheck
     */
    public function checkBatch(VoucherRedemptionBatch $batch, ?Vendor $vendor = null): RedemptionVoucherBatchCheck
    {
        $ids = $batch->getVouchers();

        $check = new RedemptionVoucherBatchCheck();
        if (empty($ids)) {
            return $check;
        }

        $vouchers = $this->em->getRepository(Voucher::class)->findBy([
            'id' => $ids,
        ]);
        $ids = array_flip($ids);

        /** @var Voucher $voucher */
        foreach ($vouchers as $voucher) {
            $error = false;
            if (Booklet::UNASSIGNED == $voucher->getBooklet()->getStatus()
                || null == $voucher->getBooklet()->getAssistanceBeneficiary()) {
                $check->addUnassignedVoucher($voucher);
                $error = true;
            }

            if (Booklet::DISTRIBUTED == $voucher->getBooklet()->getStatus()
                || null === $voucher->getVoucherPurchase()) {
                $check->addUnusedVoucher($voucher);
                $error = true;
            }

            if (Booklet::USED == $voucher->getBooklet()->getStatus()
                && null !== $voucher->getRedeemedAt()) {
                $check->addAlreadyRedeemedVoucher($voucher);
                $error = true;
            }

            if (Booklet::USED == $voucher->getBooklet()->getStatus()
                && null !== $vendor
                && null == $voucher->getRedeemedAt()
                && $vendor !== $voucher->getVoucherPurchase()->getVendor()) {
                $check->addVendorInconsistentVoucher($voucher);
                $error = true;
            }

            if (!$error) {
                $check->addValidVoucher($voucher);
            }
            unset($ids[$voucher->getId()]);
        }
        foreach (array_keys($ids) as $notExistedId) {
            $check->addNotExistedId($notExistedId);
        }

        return $check;
    }

    public function redeemBatch(Vendor $vendor, VoucherRedemptionBatch $batch, User $redeemedBy): \VoucherBundle\Entity\VoucherRedemptionBatch
    {
        $check = $this->checkBatch($batch);

        if ($check->hasInvalidVouchers()) {
            throw new \InvalidArgumentException("Invalid voucher batch");
        }

        $repository = $this->em->getRepository(Voucher::class);

        $voucherBatchValue = $repository->countVoucherValue($check->getValidVouchers());
        $redemptionBatch = new \VoucherBundle\Entity\VoucherRedemptionBatch($vendor, $redeemedBy, $check->getValidVouchers(), $voucherBatchValue);

        $this->em->persist($redemptionBatch);

        foreach ($check->getValidVouchers() as $voucher) {
            $voucher->setRedemptionBatch($redemptionBatch);
        }

        $this->em->flush();

        return $redemptionBatch;
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
        if ($removeVoucher && null === $voucher->getVoucherPurchase()) {
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
        $exportableCount = 0;
        $exportableTable = [];

        if ($ids) {
            $exportableTable = $this->em->getRepository(Voucher::class)->getAllByBookletIds($ids);
            $exportableCount = $this->em->getRepository(Voucher::class)->countByBookletsIds($ids);
        } else if ($filters) {
            /** @var DataTableType $dataTableFilter */
            $dataTableFilter = RequestConverter::normalizeInputType($filters, DataTableType::class);
            $booklets = $this->container->get('voucher.booklet_service')->getAll(new Country($countryIso3), $dataTableFilter)[1];
        } else {
            $booklets = $this->em->getRepository(Booklet::class)->getActiveBooklets($countryIso3);
        }
        
        // If we only have the booklets, get the vouchers
        if ($booklets !== null) {
            $exportableTable = $this->em->getRepository(Voucher::class)->getAllByBooklets($booklets);
            $exportableCount = $this->em->getRepository(Voucher::class)->countByBooklets($booklets);
        }

        // If csv type, return the response
        if ('csv' === $type) {
            if ($exportableCount >= $this->exportLimitCSV) {
                $totalBooklets = $ids ? count($ids) : count($booklets);
                throw new \Exception("Too much vouchers for the export ($exportableCount vouchers in $totalBooklets booklets). ".
                    "Export the data in batches of {$this->exportLimitCSV} vouchers or less");
            }
            return $this->csvExport($exportableTable);
        }

        $total = $ids ? $this->em->getRepository(Voucher::class)->countByBookletsIds($ids) : $this->em->getRepository(Voucher::class)->countByBooklets($booklets);
        if ($total > $this->exportLimit) {
            $totalBooklets = $ids ? count($ids) : count($booklets);
            throw new \Exception("Too much vouchers for the export ($total vouchers in $totalBooklets booklets). ".
            "Export the data in batches of {$this->exportLimit} vouchers or less");
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
        $exportableTable = [];

        if ($ids) {
            $exportableTable = $this->em->getRepository(Voucher::class)->getAllByBookletIds($ids)->getResult();
        } else if ($filters) {
            /** @var DataTableType $dataTableFilter */
            $dataTableFilter = RequestConverter::normalizeInputType($filters, DataTableType::class);
            $booklets = $this->container->get('voucher.booklet_service')->getAll(new Country($countryIso3), $dataTableFilter)[1];
        } else {
            $booklets = $this->em->getRepository(Booklet::class)->getActiveBooklets($countryIso3);
        }

        if ($booklets) {
            $exportableTable = $this->em->getRepository(Voucher::class)->getAllByBooklets($booklets)->getResult();
        }

        $total = $ids ? $this->em->getRepository(Voucher::class)->countByBookletsIds($ids) : $this->em->getRepository(Voucher::class)->countByBooklets($booklets);
        if ($total > $this->exportLimit) {
            $totalBooklets = $ids ? count($ids) : count($booklets);
            throw new \Exception("Too much vouchers for the export ($total vouchers in $totalBooklets). ".
                "Export the data in batches of {$this->exportLimit} vouchers or less");
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
