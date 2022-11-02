<?php

declare(strict_types=1);

namespace Component\WingMoney;

use Component\WingMoney\ValueObject\ReportEntry;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ReportParser
{
    private const FIRST_ENTRY_ROW = 9;
    //mapping
    private const TRANSACTION_DATE = 2;
    private const TRANSACTION_ID = 3;
    private const AMOUNT = 10;
    private const CURRENCY = 8;
    private const PHONE_NUMBER = 18;

    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    /**
     *
     * @return ReportEntry[]
     * @throws Exception
     */
    public function parseEntries(string $reportFilePath): array
    {
        $reader = IOFactory::createReaderForFile($reportFilePath);
        $reader->setReadDataOnly(true);

        $worksheet = $reader->load($reportFilePath)->getActiveSheet();

        $entries = [];

        for ($row = self::FIRST_ENTRY_ROW;; $row++) {
            if ($worksheet->getCellByColumnAndRow(1, $row)->getValue() === null) {
                break;
            }

            $reportEntry = new ReportEntry();
            $transactionDate = $worksheet->getCellByColumnAndRow(self::TRANSACTION_DATE, $row)->getFormattedValue();

            $reportEntry->setTransactionDate(Date::excelToDateTimeObject($transactionDate));
            $reportEntry->setTransactionId($worksheet->getCellByColumnAndRow(self::TRANSACTION_ID, $row)->getValue());
            $reportEntry->setAmount($worksheet->getCellByColumnAndRow(self::AMOUNT, $row)->getValue());
            $reportEntry->setCurrency($worksheet->getCellByColumnAndRow(self::CURRENCY, $row)->getValue());
            $reportEntry->setPhoneNumber($worksheet->getCellByColumnAndRow(self::PHONE_NUMBER, $row)->getValue());

            $violationList = $this->validator->validate($reportEntry);
            if ($violationList->count() === 0) {
                $entries[] = $reportEntry;
            }
        }

        return $entries;
    }
}
