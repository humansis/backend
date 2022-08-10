<?php

declare(strict_types=1);

namespace TransactionBundle\Export;

use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Entity\Community;
use NewApiBundle\Entity\Household;
use NewApiBundle\Entity\Institution;
use NewApiBundle\Entity\NationalId;
use NewApiBundle\Entity\Person;
use NewApiBundle\Entity\Organization;
use NewApiBundle\Entity\Assistance;
use NewApiBundle\Entity\AssistanceBeneficiary;
use NewApiBundle\Entity\Commodity;
use NewApiBundle\Entity\GeneralReliefItem;
use InvalidArgumentException;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Enum\NationalIdType;
use NewApiBundle\Enum\ReliefPackageState;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use NewApiBundle\Entity\Donor;
use Symfony\Component\Translation\TranslatorInterface;

class AssistanceSpreadsheetExport
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function export(Assistance $assistance, Organization $organization, string $filetype)
    {
        if (!in_array($filetype, ['ods', 'xlsx', 'csv'], true)) {
            throw new InvalidArgumentException('Invalid file type. Expected one of ods, xlsx, csv. '.$filetype.' given.');
        }

        $filename = 'transaction.'.$filetype;

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $this->formatCells($worksheet);
        $this->buildHeader($worksheet, $assistance, $organization);
        $this->buildBody($worksheet, $assistance);

        $writer = IOFactory::createWriter($spreadsheet, ucfirst($filetype));
        $writer->save($filename);

        return $filename;
    }

    private function formatCells(Worksheet $worksheet)
    {
        $style = [
            'font' => [
                'name' => 'Arial',
                'bold' => false,
                'size' => 10,
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ];

        $worksheet->getColumnDimension('A')->setWidth(00.650);
        $worksheet->getColumnDimension('B')->setWidth(03.888);
        $worksheet->getColumnDimension('C')->setWidth(15.888);
        $worksheet->getColumnDimension('D')->setWidth(15.888);
        $worksheet->getColumnDimension('E')->setWidth(15.888);
        $worksheet->getColumnDimension('F')->setWidth(15.888);
        $worksheet->getColumnDimension('G')->setWidth(15.888);
        $worksheet->getColumnDimension('H')->setWidth(15.888);
        $worksheet->getColumnDimension('I')->setWidth(15.888);
        $worksheet->getColumnDimension('J')->setWidth(19.888);
        $worksheet->getColumnDimension('K')->setWidth(21.032);
        $worksheet->getColumnDimension('L')->setWidth(30.032);

        $worksheet->getStyle('A1:K10000')->applyFromArray($style);
    }

    private function buildHeader(Worksheet $worksheet, Assistance $assistance, Organization $organization)
    {
        $userInputStyle = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'F2F2F2',
                ],
            ],
        ];

        $titleStyle = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'size' => 16,
                'bold' => true,
            ],
        ];

        $labelEnStyle = [
            'font' => ['bold' => true],
        ];
        $labelStyle = [
            'font' => ['italic' => true],
        ];

        $worksheet->getRowDimension(1)->setRowHeight(05.76);
        $worksheet->getRowDimension(2)->setRowHeight(66.96);
        $worksheet->getRowDimension(3)->setRowHeight(09.36);
        $worksheet->getRowDimension(4)->setRowHeight(15.12);
        $worksheet->getRowDimension(5)->setRowHeight(15.12);
        $worksheet->getRowDimension(6)->setRowHeight(09.36);
        $worksheet->getRowDimension(7)->setRowHeight(15.12);
        $worksheet->getRowDimension(8)->setRowHeight(15.12);
        $worksheet->getRowDimension(9)->setRowHeight(09.36);
        $worksheet->getRowDimension(10)->setRowHeight(13.00);
        $worksheet->getRowDimension(11)->setRowHeight(13.00);
        $worksheet->getRowDimension(12)->setRowHeight(13.00);
        $worksheet->getRowDimension(13)->setRowHeight(13.00);
        $worksheet->getRowDimension(14)->setRowHeight(09.36);
        $worksheet->getRowDimension(15)->setRowHeight(12.24);
        $worksheet->getRowDimension(16)->setRowHeight(18.00);
        $worksheet->getRowDimension(17)->setRowHeight(18.00);
        $worksheet->getRowDimension(18)->setRowHeight(12.24);

        $worksheet->getCell('B2')->setValue('DISTRIBUTION LIST');
        $worksheet->getCell('B2')->getStyle()->applyFromArray($titleStyle);
        $worksheet->mergeCells('B2:E2');

        if ($organization->getLogo()) {
            $resource = $this->getImageResource($organization->getLogo());

            $drawing = new MemoryDrawing();
            $drawing->setCoordinates('I2');
            $drawing->setImageResource($resource);
            $drawing->setRenderingFunction(MemoryDrawing::RENDERING_DEFAULT);
            $drawing->setMimeType(MemoryDrawing::MIMETYPE_DEFAULT);
            $drawing->setHeight(80);
            $drawing->setWorksheet($worksheet);
        }

        /** @var Donor $donor */
        foreach ($assistance->getProject()->getDonors() as $donor) {
            if (null === $donor->getLogo()) {
                continue;
            }

            $resource = $this->getImageResource($donor->getLogo());

            $drawing = new MemoryDrawing();
            $drawing->setCoordinates('J2');
            $drawing->setImageResource($resource);
            $drawing->setRenderingFunction(MemoryDrawing::RENDERING_DEFAULT);
            $drawing->setMimeType(MemoryDrawing::MIMETYPE_DEFAULT);
            $drawing->setHeight(80);
            $drawing->setWorksheet($worksheet);
        }

        $worksheet->getStyle('B3:K14')->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_THICK);
        $worksheet->getStyle('B3:K14')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $worksheet->getCell('C4')->setValue('Distribution No.');
        $worksheet->getCell('C4')->getStyle()->applyFromArray($labelEnStyle);

        $worksheet->getCell('C5')->setValue($this->translator->trans('Distribution No.'));
        $worksheet->getCell('C5')->getStyle()->applyFromArray($labelStyle);

        $worksheet->getCell('D4')->setValue('#'.$assistance->getId());
        $worksheet->getCell('D4')->getStyle()->applyFromArray($userInputStyle);
        $worksheet->mergeCells('D4:D5');

        $worksheet->getCell('E4')->setValue('Location:');
        $worksheet->getCell('E4')->getStyle()->applyFromArray($labelEnStyle);

        $worksheet->getCell('E5')->setValue($this->translator->trans('Location').':');
        $worksheet->getCell('E5')->getStyle()->applyFromArray($labelStyle);

        $worksheet->getCell('F4')->setValue($assistance->getLocation()->getName());
        $worksheet->getCell('F4')->getStyle()->applyFromArray($userInputStyle);
        $worksheet->mergeCells('F4:F5');

        $worksheet->getCell('G4')->setValue('Project & Donor:');
        $worksheet->getCell('G4')->getStyle()->applyFromArray($labelEnStyle);

        $worksheet->getCell('G5')->setValue($this->translator->trans('Project & Donor').':');
        $worksheet->getCell('G5')->getStyle()->applyFromArray($labelStyle);

        $worksheet->getCell('H4')->setValue(self::getProjectsAndDonors($assistance));
        $worksheet->getCell('H4')->getStyle()->applyFromArray($userInputStyle);
        $worksheet->mergeCells('H4:H5');

        $worksheet->getCell('I4')->setValue('Date:');
        $worksheet->getCell('I4')->getStyle()->applyFromArray($labelEnStyle);

        $worksheet->getCell('I5')->setValue($this->translator->trans('Date').':');
        $worksheet->getCell('I5')->getStyle()->applyFromArray($labelStyle);

        $worksheet->getCell('J4')->setValue($assistance->getDateDistribution()->format('Y-m-d'));
        $worksheet->getCell('J4')->getStyle()->applyFromArray($userInputStyle);
        $worksheet->mergeCells('J4:J5');

        $worksheet->getCell('C7')->setValue('Distributed item(s):');
        $worksheet->getCell('C7')->getStyle()->applyFromArray($labelEnStyle);

        $worksheet->getCell('C8')->setValue($this->translator->trans('Distributed item(s)').':');
        $worksheet->getCell('C8')->getStyle()->applyFromArray($labelStyle);

        $worksheet->getCell('D7')->setValue($assistance->getCommodities()->get(0)->getModalityType()->getName());
        $worksheet->getCell('D7')->getStyle()->applyFromArray($userInputStyle);
        $worksheet->mergeCells('D7:D8');

        if ($assistance->getCommodities()->get(1)) {
            $worksheet->getCell('E7')->setValue('Distributed item(s):');
            $worksheet->getCell('E7')->getStyle()->applyFromArray($labelEnStyle);

            $worksheet->getCell('E8')->setValue($this->translator->trans('Distributed item(s)').':');
            $worksheet->getCell('E8')->getStyle()->applyFromArray($labelStyle);

            $worksheet->getCell('F7')->setValue($assistance->getCommodities()->get(1)->getModalityType()->getName());
            $worksheet->getCell('F7')->getStyle()->applyFromArray($userInputStyle);
            $worksheet->getStyle('F7:F8')->applyFromArray($userInputStyle);
        }

        if ($assistance->getCommodities()->get(2)) {
            $worksheet->getCell('G7')->setValue('Distributed item(s):');
            $worksheet->getCell('G7')->getStyle()->applyFromArray($labelEnStyle);

            $worksheet->getCell('G8')->setValue($this->translator->trans('Distributed item(s)').':');
            $worksheet->getCell('G8')->getStyle()->applyFromArray($labelStyle);

            $worksheet->getCell('H7')->setValue($assistance->getCommodities()->get(2)->getModalityType()->getName());
            $worksheet->getStyle('H7')->applyFromArray($userInputStyle);
            $worksheet->mergeCells('H7:H8');
        }

        $worksheet->getCell('I7')->setValue('Round:');
        $worksheet->getCell('I7')->getStyle()->applyFromArray($labelEnStyle);

        $worksheet->getCell('I8')->setValue($this->translator->trans('Round').':');
        $worksheet->getCell('I8')->getStyle()->applyFromArray($labelStyle);

        $worksheet->getStyle('J7')->applyFromArray($userInputStyle);
        $worksheet->mergeCells('J7:J8');

        $worksheet->getCell('C10')->setValue("Distributed by:");
        $worksheet->getCell('C11')->setValue("(name, position, signature)");
        $worksheet->getCell('C12')->setValue($this->translator->trans('Distributed by'));
        $worksheet->getCell('C13')->setValue($this->translator->trans('(name, position, signature)'));
        $worksheet->getCell('C10')->getStyle()->applyFromArray($labelEnStyle);
        $worksheet->getCell('C11')->getStyle()->applyFromArray($labelEnStyle);
        $worksheet->getCell('C11')->getStyle()->getFont()->setSize(8);
        $worksheet->getCell('C12')->getStyle()->applyFromArray($labelStyle);
        $worksheet->getCell('C13')->getStyle()->applyFromArray($labelStyle);
        $worksheet->getCell('C13')->getStyle()->getFont()->setSize(8);

        $worksheet->mergeCells('D10:F13');
        $worksheet->getStyle('D10')->applyFromArray($userInputStyle);

        $worksheet->getCell('G10')->setValue("Approved by:");
        $worksheet->getCell('G11')->setValue("(name, position, signature)");
        $worksheet->getCell('G12')->setValue($this->translator->trans('Approved by'));
        $worksheet->getCell('G13')->setValue($this->translator->trans('(name, position, signature)'));
        $worksheet->getCell('G10')->getStyle()->applyFromArray($labelEnStyle);
        $worksheet->getCell('G11')->getStyle()->applyFromArray($labelEnStyle);
        $worksheet->getCell('G11')->getStyle()->getFont()->setSize(8);
        $worksheet->getCell('G12')->getStyle()->applyFromArray($labelStyle);
        $worksheet->getCell('G13')->getStyle()->applyFromArray($labelStyle);
        $worksheet->getCell('G13')->getStyle()->getFont()->setSize(8);

        $worksheet->getStyle('H10')->applyFromArray($userInputStyle);
        $worksheet->mergeCells('H10:J13');

        $worksheet->getCell('B16')->setValue('The below listed person confirm by their signature of this distribution list that they obtained and accepted the donation of the below specified items from People in Need.');
        $worksheet->mergeCells('B16:K16');

        $worksheet->getCell('B17')->setValue($this->translator->trans('The below listed person confirm by their signature of this Distribution List that they obtained and accepted the donation of the below specified items from People in Need.'));
        $worksheet->getStyle('B17')->getFont()->setItalic(true);
        $worksheet->getStyle('B16:K17')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $worksheet->mergeCells('B17:K17');
    }

    private function buildBody(Worksheet $worksheet, Assistance $assistance)
    {
        $rowStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_HAIR,
                ],
            ],
            'alignment' => [
                'wrapText' => true,
            ],
        ];

        $worksheet->getCell('B19')->setValue('No.');
        $worksheet->getCell('C19')->setValue('First Name');
        $worksheet->getCell('D19')->setValue('Second Name');
        $worksheet->getCell('E19')->setValue('ID No.');
        $worksheet->getCell('F19')->setValue('Phone No.');
        $worksheet->getCell('G19')->setValue('Proxy First Name');
        $worksheet->getCell('H19')->setValue('Proxy Second Name');
        $worksheet->getCell('I19')->setValue('Proxy ID No.');
        $worksheet->getCell('J19')->setValue('Distributed Item(s), Unit, Amount per beneficiary');
        $worksheet->getCell('K19')->setValue('Signature');
        $worksheet->getStyle('B19:K19')->applyFromArray($rowStyle);
        $worksheet->getStyle('B19:K19')->getFont()->setBold(true);
        $worksheet->getRowDimension(19)->setRowHeight(42.00);

        $worksheet->setCellValue('B20', $this->translator->trans('No.'));
        $worksheet->setCellValue('C20', $this->translator->trans('First Name'));
        $worksheet->setCellValue('D20', $this->translator->trans('Second Name'));
        $worksheet->setCellValue('E20', $this->translator->trans('ID No.'));
        $worksheet->setCellValue('F20', $this->translator->trans('Phone No.'));
        $worksheet->setCellValue('G20', $this->translator->trans('Proxy First Name'));
        $worksheet->setCellValue('H20', $this->translator->trans('Proxy Second Name'));
        $worksheet->setCellValue('I20', $this->translator->trans('Proxy ID No.'));
        $worksheet->setCellValue('J20', $this->translator->trans('Distributed Item(s), Unit, Amount per beneficiary'));
        $worksheet->setCellValue('K20', $this->translator->trans('Signature'));
        $worksheet->getStyle('B20:K20')->applyFromArray($rowStyle);
        $worksheet->getStyle('B20:K20')->getFont()->setItalic(true);
        $worksheet->getRowDimension(20)->setRowHeight(42.00);

        $worksheet->getStyle('B19:K19')->getBorders()
            ->getTop()
            ->setBorderStyle(Border::BORDER_THICK);
        $worksheet->getStyle('B20:K20')->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_THICK);
        $worksheet->getStyle('B19:K20')->getBorders()
            ->getLeft()
            ->setBorderStyle(Border::BORDER_THICK);
        $worksheet->getStyle('B19:K20')->getBorders()
            ->getRight()
            ->setBorderStyle(Border::BORDER_THICK);

        $rowNumber = 21;
        foreach ($assistance->getDistributionBeneficiaries() as  $id => $distributionBeneficiary) {
            $rowNumber = $this->createBeneficiaryRow($worksheet, $distributionBeneficiary, $rowNumber, $id+1, $rowStyle);
        }
    }

    private function createBeneficiaryRow(Worksheet $worksheet, AssistanceBeneficiary $distributionBeneficiary, $rowNumber, $id, $rowStyle) {
        $bnf = $distributionBeneficiary->getBeneficiary();
        if ($bnf instanceof Household) {
            $person = $bnf->getHouseholdHead()->getPerson();
        } elseif ($bnf instanceof Community) {
            $person = $bnf->getContact();
        } elseif ($bnf instanceof Institution) {
            $person = $bnf->getContact();
        } else {
            $person = $bnf->getPerson();
        }

        if ($distributionBeneficiary->getRemoved()) {
            $worksheet->getStyle("B$rowNumber:K$rowNumber")->getFont()->setStrikethrough(true);
            $worksheet->getStyle("K$rowNumber")->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('d9d9d9');
        }

        $worksheet->setCellValue('B'.$rowNumber, $id);
        $worksheet->setCellValue('C'.$rowNumber, $person->getLocalGivenName());
        $worksheet->setCellValue('D'.$rowNumber, $person->getLocalFamilyName());
        $worksheet->setCellValue('E'.$rowNumber, self::getNationalId($person));
        $worksheet->setCellValue('F'.$rowNumber, self::getPhone($person));
        $worksheet->setCellValue('G'.$rowNumber, null);
        $worksheet->setCellValue('H'.$rowNumber, null);
        $worksheet->setCellValue('I'.$rowNumber, self::getProxyPhone($person));
        $worksheet->setCellValue('J'.$rowNumber, $distributionBeneficiary->getRemoved() ? '' : self::getDistributedItems($distributionBeneficiary));
        $worksheet->getStyle('B'.$rowNumber.':K'.$rowNumber)->applyFromArray($rowStyle);
        $worksheet->getRowDimension($rowNumber)->setRowHeight(42.00);

        $nextRowNumber = $rowNumber + 1;

        if ($distributionBeneficiary->getJustification()) {
            $worksheet->getStyle('B'.$nextRowNumber.':K'.$nextRowNumber)
                ->applyFromArray($rowStyle)
                ->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('d9d9d9');
            $worksheet->setCellValue('B'.$nextRowNumber, $id);
            $worksheet->mergeCells("C{$nextRowNumber}:J{$nextRowNumber}");
            $worksheet->setCellValue('C'.$nextRowNumber, $distributionBeneficiary->getJustification());
            ++$nextRowNumber;
        }
        return $nextRowNumber;
    }

    private static function getProjectsAndDonors(Assistance $assistance): string
    {
        $donors = [];
        foreach ($assistance->getProject()->getDonors() as $donor) {
            $donors[] = $donor->getShortname();
        }

        return [] === $donors ? $assistance->getProject()->getName() :  $assistance->getProject()->getName().' & '.implode(', ', $donors);
    }

    private static function getNationalId(Person $person): ?string
    {
        $ids = $person->getNationalIds();
        if (count($ids) > 0) {
            $id = $ids[0];
            return $id->getIdNumber().PHP_EOL."({$id->getIdType()})";
        }
        return null;
    }

    private static function getPhone(Person $person): ?string
    {
        foreach ($person->getPhones() as $p) {
            if (!$p->getProxy()) {
                return $p->getPrefix().$p->getNumber();
            }
        }

        return null;
    }

    private static function getProxyPhone(Person $person): ?string
    {
        foreach ($person->getPhones() as $p) {
            if ($p->getProxy()) {
                return $p->getPrefix().$p->getNumber();
            }
        }

        return null;
    }

    private static function getDistributedItems(AssistanceBeneficiary $assistanceBeneficiary): ?string
    {
        $result = [];

        foreach ($assistanceBeneficiary->getSmartcardDeposits() as $deposit) {
            $result[] = 'Smartcard deposit: '.$deposit->getValue().' '.$deposit->getSmartcard()->getCurrency();
        }

        foreach ($assistanceBeneficiary->getReliefPackages() as $relief) {
            /** @var ReliefPackage $relief */
            if ($relief->getState() === ReliefPackageState::DISTRIBUTED) {
                $result[] = $relief->getModalityType().', '.$relief->getAmountToDistribute().' '.$relief->getUnit();
                break;
            }
        }

        return implode("\n", $result);
    }

    private function getImageResource(string $filename)
    {
        switch (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            case 'gif':
                return imagecreatefromgif($filename);
            case 'jpg':
            case 'jpeg':
                return imagecreatefromjpeg($filename);
            case 'png':
                return imagecreatefrompng($filename);
            default:
                throw new \LogicException('Unsupported filetype '.strtolower(pathinfo($filename, PATHINFO_EXTENSION)));
        }
    }
}
