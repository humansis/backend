<?php

declare(strict_types=1);

namespace Export;

use Entity\Beneficiary;
use Entity\NationalId;
use Entity\Phone;
use Entity\Assistance;
use Component\Country\Countries;
use Component\Country\Country;
use Enum\NationalIdType;
use InputType\PurchasedItemFilterInputType;
use IntlDateFormatter;
use InvalidArgumentException;
use Punic\Misc;
use Repository\PurchasedItemRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class PurchasedSummarySpreadsheetExport
 *
 * @package Export
 *
 * @deprecated This class is deprecated and will be removed soon
 */
class PurchasedSummarySpreadsheetExport
{
    public function __construct(private readonly TranslatorInterface $translator, private readonly Countries $countries, private readonly PurchasedItemRepository $repository)
    {
    }

    public function export(string $countryIso3, string $filetype, PurchasedItemFilterInputType $filter)
    {
        $country = $this->countries->getCountry($countryIso3);
        if (!$country) {
            throw new InvalidArgumentException('Invalid country ' . $countryIso3);
        }

        if (!in_array($filetype, ['ods', 'xlsx', 'csv'], true)) {
            throw new InvalidArgumentException(
                'Invalid file type. Expected one of ods, xlsx, csv. ' . $filetype . ' given.'
            );
        }

        $filename = sys_get_temp_dir() . '/purchased_items.' . $filetype;

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $this->build($worksheet, $country, $filter);

        $writer = IOFactory::createWriter($spreadsheet, ucfirst($filetype));
        $writer->save($filename);

        return $filename;
    }

    private function build(Worksheet $worksheet, Country $country, PurchasedItemFilterInputType $filter)
    {
        $worksheet->getColumnDimension('A')->setWidth(16.852);
        $worksheet->getColumnDimension('B')->setWidth(14.423);
        $worksheet->getColumnDimension('C')->setWidth(16.614);
        $worksheet->getColumnDimension('D')->setWidth(18.136);
        $worksheet->getColumnDimension('E')->setWidth(13.565);
        $worksheet->getColumnDimension('F')->setWidth(13.565);
        $worksheet->getColumnDimension('G')->setWidth(12.565);
        $worksheet->getColumnDimension('H')->setWidth(14.853);
        $worksheet->getColumnDimension('I')->setWidth(14.853);
        $worksheet->getColumnDimension('J')->setWidth(14.853);
        $worksheet->getColumnDimension('K')->setWidth(14.853);
        $worksheet->getColumnDimension('L')->setWidth(14.853);
        $worksheet->getColumnDimension('M')->setWidth(19.136);
        $worksheet->getColumnDimension('N')->setWidth(14.423);
        $worksheet->getColumnDimension('O')->setWidth(14.423);
        $worksheet->getColumnDimension('P')->setWidth(14.423);
        $worksheet->getColumnDimension('Q')->setWidth(08.837);
        $worksheet->getColumnDimension('R')->setWidth(14.423);
        $worksheet->getColumnDimension('S')->setWidth(14.423);
        $worksheet->getColumnDimension('T')->setWidth(28.080);
        $worksheet->getColumnDimension('U')->setWidth(14.423);
        $worksheet->getColumnDimension('V')->setWidth(14.423);
        $worksheet->getColumnDimension('W')->setWidth(28.080);
        $worksheet->getRowDimension(1)->setRowHeight(28.705);
        $worksheet->setRightToLeft('right-to-left' === Misc::getCharacterOrder($this->translator->getLocale()));
        $worksheet->getStyle('A1:W1')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'font' => [
                'size' => 11,
                'bold' => true,
            ],
        ]);

        $dateFormatter = new IntlDateFormatter(
            $this->translator->getLocale(),
            IntlDateFormatter::SHORT,
            IntlDateFormatter::NONE
        );

        $worksheet->setCellValue('A1', $this->translator->trans('Beneficiary ID'));
        $worksheet->setCellValue('B1', $this->translator->trans('Beneficiary Type'));
        $worksheet->setCellValue('C1', $this->translator->trans('Beneficiary First Name (local)'));
        $worksheet->setCellValue('D1', $this->translator->trans('Beneficiary Family Name (local)'));
        $worksheet->setCellValue('E1', $this->translator->trans('ID Number'));
        $worksheet->setCellValue('F1', $this->translator->trans('Phone'));
        $worksheet->setCellValue('G1', $this->translator->trans('Project Name'));
        $worksheet->setCellValue('H1', $this->translator->trans('Distribution Name'));
        $worksheet->setCellValue('I1', $this->translator->trans($country->getAdm1Name()));
        $worksheet->setCellValue('J1', $this->translator->trans($country->getAdm2Name()));
        $worksheet->setCellValue('K1', $this->translator->trans($country->getAdm3Name()));
        $worksheet->setCellValue('L1', $this->translator->trans($country->getAdm4Name()));
        $worksheet->setCellValue('M1', $this->translator->trans('Purchase Date & Time'));
        $worksheet->setCellValue('N1', $this->translator->trans('Commodity Type'));
        $worksheet->setCellValue('O1', $this->translator->trans('Carrier No.'));
        $worksheet->setCellValue('P1', $this->translator->trans('Item Purchased'));
        $worksheet->setCellValue('Q1', $this->translator->trans('Unit'));
        $worksheet->setCellValue('R1', $this->translator->trans('Total Cost'));
        $worksheet->setCellValue('S1', $this->translator->trans('Currency'));
        $worksheet->setCellValue('T1', $this->translator->trans('Vendor Name'));
        $worksheet->setCellValue('U1', $this->translator->trans('Vendor Humansis ID'));
        $worksheet->setCellValue('V1', $this->translator->trans('Vendor Nr.'));
        $worksheet->setCellValue('W1', $this->translator->trans('Humansis Invoice Nr.'));

        $i = 1;
        foreach ($this->repository->findByParams($country->getIso3(), $filter) as $purchasedItem) {
            $beneficiary = $purchasedItem->getBeneficiary();
            $assistance = $purchasedItem->getAssistance();
            $commodity = $purchasedItem->getCommodity();
            $datetime = $purchasedItem->getDatePurchase();
            $fullLocation = self::adms($assistance);

            $i++;
            $worksheet->setCellValue('A' . $i, $beneficiary->getId());
            $worksheet->setCellValue(
                'B' . $i,
                $beneficiary->isHead() ? $this->translator->trans('Household') : $this->translator->trans('Individual')
            );
            $worksheet->setCellValue('C' . $i, $beneficiary->getLocalGivenName());
            $worksheet->setCellValue('D' . $i, $beneficiary->getLocalFamilyName());
            $worksheet->setCellValue('E' . $i, self::nationalId($beneficiary) ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('F' . $i, self::phone($beneficiary) ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('G' . $i, $purchasedItem->getProject()->getName());
            $worksheet->setCellValue('H' . $i, $assistance->getName());
            $worksheet->setCellValue('I' . $i, $fullLocation[0]);
            $worksheet->setCellValue('J' . $i, $fullLocation[1]);
            $worksheet->setCellValue('K' . $i, $fullLocation[2]);
            $worksheet->setCellValue('L' . $i, $fullLocation[3]);
            $worksheet->setCellValue(
                'M' . $i,
                $datetime ? $dateFormatter->format($datetime) : $this->translator->trans('N/A')
            );
            $worksheet->setCellValue('N' . $i, $purchasedItem->getModalityType());
            $worksheet->setCellValue('O' . $i, $purchasedItem->getCarrierNumber() ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('P' . $i, $purchasedItem->getProduct()->getName());
            $worksheet->setCellValue('Q' . $i, $commodity->getUnit());
            $worksheet->setCellValue('R' . $i, $purchasedItem->getValue());
            $worksheet->setCellValue('S' . $i, $purchasedItem->getCurrency());
            $worksheet->setCellValue(
                'T' . $i,
                $purchasedItem->getVendor()->getName() ?? $this->translator->trans('N/A')
            );
            $worksheet->setCellValue('U' . $i, $purchasedItem->getVendor()->getId());
            $worksheet->setCellValue(
                'V' . $i,
                $purchasedItem->getVendor()->getVendorNo() ?? $this->translator->trans('N/A')
            );
            $worksheet->setCellValue('W' . $i, $purchasedItem->getInvoiceNumber() ?? $this->translator->trans('N/A'));
        }
    }

    private static function phone(Beneficiary $beneficiary): ?string
    {
        /** @var Phone $phone */
        foreach ($beneficiary->getPerson()->getPhones() as $phone) {
            if (!$phone->getProxy()) {
                return $phone->getPrefix() . ' ' . $phone->getNumber();
            }
        }

        return null;
    }

    private static function nationalId(Beneficiary $beneficiary): ?string
    {
        /** @var NationalId $nationalId */
        foreach ($beneficiary->getPerson()->getNationalIds() as $nationalId) {
            if (NationalIdType::NATIONAL_ID === $nationalId->getIdType()) {
                return $nationalId->getIdNumber();
            }
        }

        return null;
    }

    //TODO: fullLocationNames - move to a helper class?
    private static function adms(Assistance $assistance): array
    {
        $location = $assistance->getLocation();
        $names = array_fill(0, 4, null);

        while ($location) {
            $names[$location->getLvl() - 1] = $location->getName();
            $location = $location->getParent();
        }

        return $names;
    }
}
