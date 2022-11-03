<?php

declare(strict_types=1);

namespace Export;

use Entity\Assistance;
use Component\Country\Countries;
use Component\Country\Country;
use InputType\SmartcardPurchasedItemFilterInputType;
use IntlDateFormatter;
use InvalidArgumentException;
use Punic\Misc;
use Repository\SmartcardPurchasedItemRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Contracts\Translation\TranslatorInterface;

class SmartcardPurchasedItemSpreadsheet
{
    public function __construct(
        private readonly SmartcardPurchasedItemRepository $repository,
        private readonly TranslatorInterface $translator,
        private readonly Countries $countries
    ) {
    }

    public function export(string $countryIso3, string $filetype, SmartcardPurchasedItemFilterInputType $filter): string
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

    private function build(Worksheet $worksheet, Country $country, SmartcardPurchasedItemFilterInputType $filter): void
    {
        $worksheet->getColumnDimension('A')->setWidth(16.852);
        $worksheet->getColumnDimension('B')->setWidth(16.852);
        $worksheet->getColumnDimension('C')->setWidth(16.614);
        $worksheet->getColumnDimension('D')->setWidth(18.136);
        $worksheet->getColumnDimension('E')->setWidth(13.565);
        $worksheet->getColumnDimension('F')->setWidth(13.565);
        $worksheet->getColumnDimension('G')->setWidth(13.565);
        $worksheet->getColumnDimension('H')->setWidth(13.565);
        $worksheet->getColumnDimension('I')->setWidth(13.565);
        $worksheet->getColumnDimension('J')->setWidth(13.565);
        $worksheet->getColumnDimension('K')->setWidth(13.565);
        $worksheet->getColumnDimension('L')->setWidth(12.565);
        $worksheet->getColumnDimension('M')->setWidth(14.853);
        $worksheet->getColumnDimension('N')->setWidth(14.853);
        $worksheet->getColumnDimension('O')->setWidth(14.853);
        $worksheet->getColumnDimension('P')->setWidth(14.853);
        $worksheet->getColumnDimension('Q')->setWidth(14.853);
        $worksheet->getColumnDimension('R')->setWidth(14.853);
        $worksheet->getColumnDimension('S')->setWidth(19.136);
        $worksheet->getColumnDimension('T')->setWidth(14.423);
        $worksheet->getColumnDimension('U')->setWidth(14.423);
        $worksheet->getColumnDimension('V')->setWidth(08.837);
        $worksheet->getColumnDimension('W')->setWidth(14.423);
        $worksheet->getColumnDimension('X')->setWidth(14.423);
        $worksheet->getColumnDimension('Y')->setWidth(28.080);
        $worksheet->getColumnDimension('Z')->setWidth(14.423);
        $worksheet->getColumnDimension('AA')->setWidth(14.423);
        $worksheet->getColumnDimension('AB')->setWidth(28.080);
        $worksheet->getRowDimension(1)->setRowHeight(28.705);
        $worksheet->setRightToLeft('right-to-left' === Misc::getCharacterOrder($this->translator->getLocale()));
        $worksheet->getStyle('A1:AB1')->applyFromArray([
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

        $worksheet->setCellValue('A1', $this->translator->trans('Household ID'));
        $worksheet->setCellValue('B1', $this->translator->trans('Beneficiary ID'));
        $worksheet->setCellValue('C1', $this->translator->trans('Beneficiary First Name (local)'));
        $worksheet->setCellValue('D1', $this->translator->trans('Beneficiary Family Name (local)'));
        $worksheet->setCellValue('E1', 'Primary ID Type');
        $worksheet->setCellValue('F1', 'Primary ID Number');
        $worksheet->setCellValue('G1', 'Secondary ID Type');
        $worksheet->setCellValue('H1', 'Secondary ID Number');
        $worksheet->setCellValue('I1', 'Tertiary ID Type');
        $worksheet->setCellValue('J1', 'Tertiary ID Number');
        $worksheet->setCellValue('K1', $this->translator->trans('Phone'));
        $worksheet->setCellValue('L1', $this->translator->trans('Project Name'));
        $worksheet->setCellValue('M1', $this->translator->trans('Distribution Name'));
        $worksheet->setCellValue('N1', $this->translator->trans('Round'));
        $worksheet->setCellValue('O1', $this->translator->trans($country->getAdm1Name()));
        $worksheet->setCellValue('P1', $this->translator->trans($country->getAdm2Name()));
        $worksheet->setCellValue('Q1', $this->translator->trans($country->getAdm3Name()));
        $worksheet->setCellValue('R1', $this->translator->trans($country->getAdm4Name()));
        $worksheet->setCellValue('S1', $this->translator->trans('Purchase Date & Time'));
        $worksheet->setCellValue('T1', $this->translator->trans('Smartcard code'));
        $worksheet->setCellValue('U1', $this->translator->trans('Item Purchased'));
        $worksheet->setCellValue('V1', $this->translator->trans('Unit'));
        $worksheet->setCellValue('W1', $this->translator->trans('Total Cost'));
        $worksheet->setCellValue('X1', $this->translator->trans('Currency'));
        $worksheet->setCellValue('Y1', $this->translator->trans('Vendor Name'));
        $worksheet->setCellValue('Z1', $this->translator->trans('Vendor Humansis ID'));
        $worksheet->setCellValue('AA1', $this->translator->trans('Vendor Nr.'));
        $worksheet->setCellValue('AB1', $this->translator->trans('Humansis Invoice Nr.'));

        $i = 1;
        foreach ($this->repository->findByParams($country->getIso3(), $filter) as $purchasedItem) {
            $beneficiary = $purchasedItem->getBeneficiary();
            $assistance = $purchasedItem->getAssistance();
            $datetime = $purchasedItem->getDatePurchase();
            $fullLocation = self::adms($assistance);
            $primaryNationalId = $beneficiary->getPerson()->getPrimaryNationalId();
            $secondaryNationalId = $beneficiary->getPerson()->getSecondaryNationalId();
            $tertiaryNationalId = $beneficiary->getPerson()->getTertiaryNationalId();

            $i++;
            $worksheet->setCellValue('A' . $i, $purchasedItem->getHousehold()->getId());
            $worksheet->setCellValue('B' . $i, $beneficiary->getId());
            $worksheet->setCellValue('C' . $i, $beneficiary->getPerson()->getLocalGivenName());
            $worksheet->setCellValue('D' . $i, $beneficiary->getPerson()->getLocalFamilyName());
            $worksheet->setCellValue(
                'E' . $i,
                $primaryNationalId ? $this->translator->trans(
                    $primaryNationalId->getIdType()
                ) : $this->translator->trans('N/A')
            );
            $worksheet->setCellValue(
                'F' . $i,
                $primaryNationalId ? $primaryNationalId->getIdNumber() : $this->translator->trans('N/A')
            );
            $worksheet->setCellValue(
                'G' . $i,
                $secondaryNationalId ? $this->translator->trans(
                    $secondaryNationalId->getIdType()
                ) : $this->translator->trans('N/A')
            );
            $worksheet->setCellValue(
                'H' . $i,
                $secondaryNationalId ? $secondaryNationalId->getIdNumber() : $this->translator->trans('N/A')
            );
            $worksheet->setCellValue(
                'I' . $i,
                $tertiaryNationalId ? $this->translator->trans(
                    $tertiaryNationalId->getIdType()
                ) : $this->translator->trans('N/A')
            );
            $worksheet->setCellValue(
                'J' . $i,
                $tertiaryNationalId ? $tertiaryNationalId->getIdNumber() : $this->translator->trans('N/A')
            );
            $worksheet->setCellValue(
                'K' . $i,
                $beneficiary->getPerson()->getFirstPhoneWithPrefix() ?? $this->translator->trans('N/A')
            );
            $worksheet->setCellValue('L' . $i, $purchasedItem->getProject()->getName());
            $worksheet->setCellValue('M' . $i, $assistance->getName());
            $worksheet->setCellValue('N' . $i, $assistance->getRound() ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('O' . $i, $fullLocation[0]);
            $worksheet->setCellValue('P' . $i, $fullLocation[1]);
            $worksheet->setCellValue('Q' . $i, $fullLocation[2]);
            $worksheet->setCellValue('R' . $i, $fullLocation[3]);
            $worksheet->setCellValue(
                'S' . $i,
                $datetime ? $dateFormatter->format($datetime) : $this->translator->trans('N/A')
            );
            $worksheet->setCellValue('T' . $i, $purchasedItem->getSmartcardCode() ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('U' . $i, $purchasedItem->getProduct()->getName());
            $worksheet->setCellValue('V' . $i, $purchasedItem->getProduct()->getUnit());
            $worksheet->setCellValue('W' . $i, $purchasedItem->getValue());
            $worksheet->setCellValue('X' . $i, $purchasedItem->getCurrency());
            $worksheet->setCellValue(
                'Y' . $i,
                $purchasedItem->getVendor()->getName() ?? $this->translator->trans('N/A')
            );
            $worksheet->setCellValue('Z' . $i, $purchasedItem->getVendor()->getId());
            $worksheet->setCellValue(
                'AA' . $i,
                $purchasedItem->getVendor()->getVendorNo() ?? $this->translator->trans('N/A')
            );
            $worksheet->setCellValue('AB' . $i, $purchasedItem->getInvoiceNumber() ?? $this->translator->trans('N/A'));
        }
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
