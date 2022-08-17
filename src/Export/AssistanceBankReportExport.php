<?php
declare(strict_types=1);

namespace Export;


use Repository\CountrySpecificRepository;
use Entity\Assistance;
use Repository\AssistanceBeneficiaryRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Translation\TranslatorInterface;

class AssistanceBankReportExport
{

    const COUNTRY_SPECIFIC_ID_NUMBER = 'Secondary ID Number';
    const COUNTRY_SPECIFIC_ID_TYPE = 'Secondary ID Type';

    /** @var TranslatorInterface */
    private $translator;

    /** @var AssistanceBeneficiaryRepository */
    private $assistanceBeneficiaryRepository;

    /** @var CountrySpecificRepository */
    private $countrySpecificRepository;



    public function __construct(AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository, CountrySpecificRepository $countrySpecificRepository,  TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->assistanceBeneficiaryRepository = $assistanceBeneficiaryRepository;
        $this->countrySpecificRepository = $countrySpecificRepository;
    }

    public function export(Assistance $assistance, string $filetype): string
    {
        if (!in_array($filetype, ['ods', 'xlsx', 'csv'], true)) {
            throw new \InvalidArgumentException('Invalid file type. Expected one of ods, xlsx, csv. '.$filetype.' given.');
        }
        $filename = sys_get_temp_dir().'/bank-report.'.$filetype;
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $countrySpecific1 = $this->countrySpecificRepository->findOneBy(['fieldString' => self::COUNTRY_SPECIFIC_ID_TYPE, 'countryIso3' => $assistance->getProject()->getIso3()]);
        $countrySpecific2 = $this->countrySpecificRepository->findOneBy(['fieldString' => self::COUNTRY_SPECIFIC_ID_NUMBER, 'countryIso3' => $assistance->getProject()->getIso3()]);
        $this->build($worksheet, $this->assistanceBeneficiaryRepository->getBeneficiaryReliefCompilation($assistance, $countrySpecific1, $countrySpecific2));
        $writer = IOFactory::createWriter($spreadsheet, ucfirst($filetype));
        $writer->save($filename);
        return $filename;
    }

    private function build(Worksheet $worksheet, $distributions): void
    {
        $this->setupColumnHeaders($worksheet);
        $this->createColumnHeaders($worksheet);
        $this->generateRows($worksheet, $distributions);
    }

    private function setupColumnHeaders(Worksheet $worksheet) {
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
        $worksheet->getRowDimension(1)->setRowHeight(45);
        $worksheet->setRightToLeft('right-to-left' === \Punic\Misc::getCharacterOrder($this->translator->getLocale()));
        $worksheet->getStyle('A1:K1')->applyFromArray([
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
    }

    private function createColumnHeaders(Worksheet $worksheet) {
        $worksheet->setCellValue('A1', $this->translator->trans('Ordinal number'));
        $worksheet->setCellValue('B1', $this->translator->trans('Recipient’s surname (Local family name)'));
        $worksheet->setCellValue('C1', $this->translator->trans('Recipient’s name (Local given name)'));
        $worksheet->setCellValue('D1', $this->translator->trans('Recipient’s patronymic (Local parent\'s name)'));
        $worksheet->setCellValue('E1', $this->translator->trans('Recipient’s RNTRC (Tax Number)'));
        $worksheet->setCellValue('F1', $this->translator->trans('Document type'));
        $worksheet->setCellValue('G1', $this->translator->trans('Document number'));
        $worksheet->setCellValue('H1', $this->translator->trans('Remittance purpose'));
        $worksheet->setCellValue('I1', $this->translator->trans('Remittance amount'));
        $worksheet->setCellValue('J1', $this->translator->trans('Remittance currency'));
        $worksheet->setCellValue('K1', $this->translator->trans('Recipient’s mobile telephone number'));
    }

    private function generateRows(Worksheet $worksheet, $distributions) {
        $i = 1;

        foreach ( $distributions as $distribution) {
            $i++;
            $worksheet->setCellValue('A'.$i, $distribution['distributionId']);
            $worksheet->setCellValue('B'.$i, $distribution['localFamilyName']);
            $worksheet->setCellValue('C'.$i, $distribution['localGivenName']);
            $worksheet->setCellValue('D'.$i, $distribution['localParentsName']);
            $worksheet->setCellValue('E'.$i, $distribution['idNumber']);
            $worksheet->setCellValue('F'.$i, $distribution['countrySpecificValue1']);
            $worksheet->setCellValue('G'.$i, $distribution['countrySpecificValue2']);
            $worksheet->setCellValue('H'.$i, 'Благодійна допомога');
            $worksheet->setCellValue('I'.$i, $distribution['amountToDistribute']);
            $worksheet->setCellValue('J'.$i, $distribution['currency']);
            $worksheet->setCellValue('K'.$i, $distribution['phoneNumber']);

        }
    }

}
