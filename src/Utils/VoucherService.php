<?php

namespace Utils;

use Controller\ExportController;
use Exception;
use InputType\Country;
use InputType\DataTableType;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Twig\Environment;
use Entity\User;
use DTO\RedemptionVoucherBatchCheck;
use Entity\Booklet;
use Entity\Vendor;
use Entity\Voucher;
use Symfony\Component\HttpFoundation\StreamedResponse;
use InputType\VoucherRedemptionBatch;

class VoucherService
{
    /**
     * UserService constructor.
     */
    public function __construct(private readonly EntityManagerInterface $em, private readonly Environment $twig, private readonly PdfService $pdfService)
    {
    }

    /**
     * Creates a new Voucher entity
     *
     *
     * @return array
     * @throws Exception
     */
    public function create(array $vouchersData, bool $flush = true)
    {
        $vouchers = [];
        try {
            $currentId = array_key_exists('lastId', $vouchersData)
                ? $vouchersData['lastId'] + 1
                : $this->getLastId() + 1;
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
        } catch (Exception $e) {
            throw $e;
        }

        return $vouchers;
    }

    /**
     * Generate a new random code for a voucher
     *
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
            if (
                Booklet::UNASSIGNED == $voucher->getBooklet()->getStatus()
                || null == $voucher->getBooklet()->getAssistanceBeneficiary()
            ) {
                $check->addUnassignedVoucher($voucher);
                $error = true;
            }

            if (
                Booklet::DISTRIBUTED == $voucher->getBooklet()->getStatus()
                || null === $voucher->getVoucherPurchase()
            ) {
                $check->addUnusedVoucher($voucher);
                $error = true;
            }

            if (
                Booklet::USED == $voucher->getBooklet()->getStatus()
                && null !== $voucher->getRedeemedAt()
            ) {
                $check->addAlreadyRedeemedVoucher($voucher);
                $error = true;
            }

            if (
                Booklet::USED == $voucher->getBooklet()->getStatus()
                && null !== $vendor
                && null == $voucher->getRedeemedAt()
                && $vendor !== $voucher->getVoucherPurchase()->getVendor()
            ) {
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

    public function redeemBatch(
        Vendor $vendor,
        VoucherRedemptionBatch $batch,
        User $redeemedBy
    ): \Entity\VoucherRedemptionBatch {
        $check = $this->checkBatch($batch);

        if ($check->hasInvalidVouchers()) {
            throw new InvalidArgumentException("Invalid voucher batch");
        }

        $repository = $this->em->getRepository(Voucher::class);

        $voucherBatchValue = $repository->countVoucherValue($check->getValidVouchers());
        $redemptionBatch = new \Entity\VoucherRedemptionBatch(
            $vendor,
            $redeemedBy,
            $check->getValidVouchers(),
            $voucherBatchValue
        );

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
     * @return bool
     * @throws Exception
     */
    public function deleteOneFromDatabase(Voucher $voucher, bool $removeVoucher = true)
    {
        if ($removeVoucher && null === $voucher->getVoucherPurchase()) {
            $this->em->remove($voucher);
            $this->em->flush();
        } else {
            throw new Exception('$voucher has been used, unable to delete');
        }

        return true;
    }

    // =============== DELETE A BATCH OF VOUCHERS ===============
    /**
     * Deletes all the vouchers of the given booklet
     *
     * @return bool
     * @throws Exception
     */
    public function deleteBatchVouchers(Booklet $booklet)
    {
        $bookletId = $booklet->getId();
        $vouchers = $this->em->getRepository(Voucher::class)->findBy(['booklet' => $bookletId]);
        foreach ($vouchers as $value) {
            $this->deleteOneFromDatabase($value);
        }

        return true;
    }



    /**
     * Export all vouchers in a pdf
     * @param array $exportableTable
     * @return mixed
     */
    public function exportToPdf(array $exportableTable)
    {
        $total = count($exportableTable);
        if ($total > ExportController::EXPORT_LIMIT) {
            throw new Exception(
                "Too much vouchers ($total) for the export" .
                "Export the data in batches of " . ExportController::EXPORT_LIMIT . " vouchers or less"
            );
        }
        try {
            $html = $this->twig->render(
                'Pdf/codes.html.twig',
                array_merge(
                    ['vouchers' => $exportableTable],
                    $this->pdfService->getInformationStyle()
                )
            );

            $response = $this->pdfService->printPdf($html, 'portrait', 'bookletCodes');

            return $response;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    public function getLastId()
    {
        $lastVoucher = $this->em->getRepository(Voucher::class)->findBy([], ['id' => 'DESC'], 1);

        return $lastVoucher ? $lastVoucher[0]->getId() : 0;
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
            fputcsv($csv, ['Booklet Number', 'Voucher Codes'], ';');

            while (false !== ($row = $data->next())) {
                fputcsv($csv, [$row[0]->getBooklet()->getCode(), $row[0]->getCode()], ';');
                $this->em->detach($row[0]);
            }
            fclose($csv);
        });
        $response->setStatusCode(\Symfony\Component\HttpFoundation\Response::HTTP_OK);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="bookletCodes.csv"');

        return $response;
    }

    /**
     * copy&paste Utils\BookletService::getAll()
     * @deprecated
     */
    private function getAllBooklets(Country $countryISO3, DataTableType $filter): array
    {
        $limitMinimum = $filter->pageIndex * $filter->pageSize;

        $booklets = $this->em->getRepository(Booklet::class)->getAllBy(
            $countryISO3->getIso3(),
            $limitMinimum,
            $filter->pageSize,
            $filter->getSort(),
            $filter->getFilter()
        );
        $length = $booklets[0];
        $booklets = $booklets[1];

        return [$length, $booklets];
    }
}
