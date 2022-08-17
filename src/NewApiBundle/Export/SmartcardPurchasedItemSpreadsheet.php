<?php
declare(strict_types=1);

namespace NewApiBundle\Export;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use DistributionBundle\Entity\Assistance;
use NewApiBundle\Component\Country\Countries;
use NewApiBundle\Component\Country\Country;
use NewApiBundle\Enum\NationalIdType;
use NewApiBundle\InputType\SmartcardPurchasedItemFilterInputType;
use NewApiBundle\Repository\SmartcardPurchasedItemRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Translation\TranslatorInterface;

class SmartcardPurchasedItemSpreadsheet
{
    /** @var SmartcardPurchasedItemRepository */
    private $repository;

    /** @var TranslatorInterface */
    private $translator;

    /** @var Countries */
    private $countries;

    public function __construct(SmartcardPurchasedItemRepository $repository, TranslatorInterface $translator, Countries $countries )
    {
        $this->repository = $repository;
        $this->translator = $translator;
        $this->countries = $countries;
    }

    public function export(string $countryIso3, string $filetype, SmartcardPurchasedItemFilterInputType $filter): string
    {
        $country = $this->countries->getCountry($countryIso3);
        if (!$country) {
            throw new \InvalidArgumentException('Invalid country '.$countryIso3);
        }

        if (!in_array($filetype, ['ods', 'xlsx', 'csv'], true)) {
            throw new \InvalidArgumentException('Invalid file type. Expected one of ods, xlsx, csv. '.$filetype.' given.');
        }

        $filename = sys_get_temp_dir().'/purchased_items.'.$filetype;

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
        $worksheet->getColumnDimension('G')->setWidth(12.565);
        $worksheet->getColumnDimension('H')->setWidth(14.853);
        $worksheet->getColumnDimension('I')->setWidth(14.853);
        $worksheet->getColumnDimension('J')->setWidth(14.853);
        $worksheet->getColumnDimension('K')->setWidth(14.853);
        $worksheet->getColumnDimension('L')->setWidth(14.853);
        $worksheet->getColumnDimension('M')->setWidth(14.853);
        $worksheet->getColumnDimension('N')->setWidth(19.136);
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
        $worksheet->setRightToLeft('right-to-left' === \Punic\Misc::getCharacterOrder($this->translator->getLocale()));
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

        $dateFormatter = new \IntlDateFormatter($this->translator->getLocale(), \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE);

        $worksheet->setCellValue('A1', $this->translator->trans('Household ID'));
        $worksheet->setCellValue('B1', $this->translator->trans('Beneficiary ID'));
        $worksheet->setCellValue('C1', $this->translator->trans('Beneficiary First Name (local)'));
        $worksheet->setCellValue('D1', $this->translator->trans('Beneficiary Family Name (local)'));
        $worksheet->setCellValue('E1', $this->translator->trans('ID Number'));
        $worksheet->setCellValue('F1', $this->translator->trans('Phone'));
        $worksheet->setCellValue('G1', $this->translator->trans('Project Name'));
        $worksheet->setCellValue('H1', $this->translator->trans('Distribution Name'));
        $worksheet->setCellValue('I1', $this->translator->trans('Round'));
        $worksheet->setCellValue('J1', $this->translator->trans($country->getAdm1Name()));
        $worksheet->setCellValue('K1', $this->translator->trans($country->getAdm2Name()));
        $worksheet->setCellValue('L1', $this->translator->trans($country->getAdm3Name()));
        $worksheet->setCellValue('M1', $this->translator->trans($country->getAdm4Name()));
        $worksheet->setCellValue('N1', $this->translator->trans('Purchase Date & Time'));
        $worksheet->setCellValue('O1', $this->translator->trans('Smartcard code'));
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
            $datetime = $purchasedItem->getDatePurchase();
            $fullLocation = self::adms($assistance);

            $i++;
            $worksheet->setCellValue('A'.$i, $purchasedItem->getHousehold()->getId());
            $worksheet->setCellValue('B'.$i, $beneficiary->getId());
            $worksheet->setCellValue('C'.$i, $beneficiary->getPerson()->getLocalGivenName());
            $worksheet->setCellValue('D'.$i, $beneficiary->getPerson()->getLocalFamilyName());
            $worksheet->setCellValue('E'.$i, self::nationalId($beneficiary) ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('F'.$i, self::phone($beneficiary) ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('G'.$i, $purchasedItem->getProject()->getName());
            $worksheet->setCellValue('H'.$i, $assistance->getName());
            $worksheet->setCellValue('I'.$i, $assistance->getRound() ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('J'.$i, $fullLocation[0]);
            $worksheet->setCellValue('K'.$i, $fullLocation[1]);
            $worksheet->setCellValue('L'.$i, $fullLocation[2]);
            $worksheet->setCellValue('M'.$i, $fullLocation[3]);
            $worksheet->setCellValue('N'.$i, $datetime ? $dateFormatter->format($datetime) : $this->translator->trans('N/A'));
            $worksheet->setCellValue('O'.$i, $purchasedItem->getSmartcardCode() ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('Q'.$i, $purchasedItem->getProduct()->getUnit());
            $worksheet->setCellValue('P'.$i, $purchasedItem->getProduct()->getName());
            $worksheet->setCellValue('R'.$i, $purchasedItem->getValue());
            $worksheet->setCellValue('S'.$i, $purchasedItem->getCurrency());
            $worksheet->setCellValue('T'.$i, $purchasedItem->getVendor()->getName() ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('U'.$i, $purchasedItem->getVendor()->getId());
            $worksheet->setCellValue('W'.$i, $purchasedItem->getInvoiceNumber() ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('V'.$i, $purchasedItem->getVendor()->getVendorNo() ?? $this->translator->trans('N/A'));
        }
    }

    private static function phone(Beneficiary $beneficiary): ?string
    {
        /** @var Phone $phone */
        foreach ($beneficiary->getPerson()->getPhones() as $phone) {
            if (!$phone->getProxy()) {
                return $phone->getPrefix().' '.$phone->getNumber();
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
        $names = array_fill(0, 4 , null);
        
        while ($location) {
            $names[$location->getLvl() - 1] = $location->getName();
            $location = $location->getParent();
        }
        
        return $names;
    }
}
