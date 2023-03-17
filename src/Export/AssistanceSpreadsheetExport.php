<?php

declare(strict_types=1);

namespace Export;

use Component\Smartcard\SmartcardDepositService;
use Entity\Community;
use Entity\Household;
use Entity\Institution;
use Entity\Person;
use Entity\Organization;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Entity\SmartcardDeposit;
use Entity\User;
use Exception;
use InvalidArgumentException;
use Enum\ReliefPackageState;
use Repository\Assistance\ReliefPackageRepository;
use Services\CountryLocaleResolverService;
use Utils\FileSystem\Exception\ImageException;
use Utils\FileSystem\Image;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Entity\Donor;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AssistanceSpreadsheetExport
{
    /** @var SmartcardDeposit[] */
    private array $smartCardDeposits = [];

    private const GDPR_TEXT_1 = "Privacy notice: Please note that PIN as the Personal Data Controller (contact details of the Data Protection Officer: dpo@clovekvtisni.cz), will be processing your above-mentioned personal data. PIN will use the data only for the purpose of providing assistance within the project you agreed to participate in. PIN needs these data because 1) it is necessary for the provision of assistance to you according to the project terms, and 2) PIN has a legitimate interest in reporting of the project results to the donor. PIN will keep the data only for the period required by the donors financing the project, or by the legislation binding for PIN; however, the maximum period of storage is 10 years. Your data may also be shared with other persons for the purpose of implementation and verification of the project, i.e. the service providers of PIN's systems and software, where your data are stored, our project partners, donors and the auditors.";
    private const GDPR_TEXT_2 = "You have the following rights: 1) right to request information on which personal data of yours PIN is processing, 2) right to request explanation from PIN regarding the processing of personal data, 3) right to request access to such data from PIN, right to have the data updated, corrected or restricted, as the case may be, and right to object to processing, 4) the right to obtain personal data in a structured, commonly used and machine-readable format, 5) right to request the deletion of such personal data from PIN, 6) right to address the Controller or lodge a complaint to the Office for Personal Data Protection in case of doubt regarding the compliance with the obligations related to the processing of personal data.";

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly SmartcardDepositService $smartcardDepositService,
        private readonly CountryLocaleResolverService $countryLocaleResolverService,
        private readonly LoggerInterface $logger,
        private readonly ReliefPackageRepository $reliefPackageRepository,
    )
    {
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws Exception
     */
    public function export(Assistance $assistance, Organization $organization, string $filetype): string
    {
        if (!in_array($filetype, ['ods', 'xlsx', 'csv'], true)) {
            throw new InvalidArgumentException(
                'Invalid file type. Expected one of ods, xlsx, csv. ' . $filetype . ' given.'
            );
        }

        $filename = 'transaction.' . $filetype;

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $this->formatCells($worksheet);
        $languageCode = $this->countryLocaleResolverService->resolve($assistance->getProject()->getCountryIso3());
        $this->buildHeader($worksheet, $assistance, $organization, $languageCode);
        $this->buildBody($worksheet, $assistance, $languageCode);

        $writer = IOFactory::createWriter($spreadsheet, ucfirst($filetype));
        $writer->save($filename);

        return $filename;
    }

    private function formatCells(Worksheet $worksheet): void
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
        $worksheet->getColumnDimension('G')->setWidth(17.888);
        $worksheet->getColumnDimension('H')->setWidth(15.888);
        $worksheet->getColumnDimension('I')->setWidth(13.888);
        $worksheet->getColumnDimension('J')->setWidth(19.888);
        $worksheet->getColumnDimension('K')->setWidth(21.032);

        $worksheet->getStyle('A1:K10000')->applyFromArray($style);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function buildHeader(
        Worksheet $worksheet,
        Assistance $assistance,
        Organization $organization,
        string $languageCode
    ): void {
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
                'wrapText' => true,
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
        $worksheet->getRowDimension(10)->setRowHeight(15.12);
        $worksheet->getRowDimension(11)->setRowHeight(15.12);
        $worksheet->getRowDimension(12)->setRowHeight(09.36);
        $worksheet->getRowDimension(13)->setRowHeight(13.00);
        $worksheet->getRowDimension(14)->setRowHeight(13.00);
        $worksheet->getRowDimension(15)->setRowHeight(13.00);
        $worksheet->getRowDimension(16)->setRowHeight(13.00);
        $worksheet->getRowDimension(17)->setRowHeight(09.36);
        $worksheet->getRowDimension(18)->setRowHeight(12.24);
        $worksheet->getRowDimension(19)->setRowHeight(18.00);
        $worksheet->getRowDimension(20)->setRowHeight(18.00);
        $worksheet->getRowDimension(21)->setRowHeight(8.24);
        $worksheet->getRowDimension(22)->setRowHeight(85.00);
        $worksheet->getRowDimension(23)->setRowHeight(85.00);
        $worksheet->getRowDimension(24)->setRowHeight(12.24);

        $worksheet->getCell('B2')->setValue(
            'DISTRIBUTION PROTOCOL' . "\n" .
            $assistance->getName() . "\n" .
            $this->translator->trans('DISTRIBUTION PROTOCOL', [], null, $languageCode)
        );
        $worksheet->getCell('B2')->getStyle()->applyFromArray($titleStyle);
        $worksheet->mergeCells('B2:F2');

        if ($organization->getLogo()) {
            try {
                $resource = Image::getImageResource($organization->getLogo());
                $drawing = new MemoryDrawing();
                $drawing->setCoordinates('H2');
                $drawing->setImageResource($resource);
                $drawing->setRenderingFunction(MemoryDrawing::RENDERING_DEFAULT);
                $drawing->setMimeType(MemoryDrawing::MIMETYPE_DEFAULT);
                $drawing->setHeight(80);
                $drawing->setWorksheet($worksheet);
            } catch (ImageException $exception) {
                $this->logger->info($exception->getMessage());
            }
        }

        /** @var Donor $donor */
        foreach ($assistance->getProject()->getDonors() as $donor) {
            if (null === $donor->getLogo()) {
                continue;
            }

            try {
                $resource = Image::getImageResource($donor->getLogo());
                $drawing = new MemoryDrawing();
                $drawing->setCoordinates('J2');
                $drawing->setImageResource($resource);
                $drawing->setRenderingFunction(MemoryDrawing::RENDERING_DEFAULT);
                $drawing->setMimeType(MemoryDrawing::MIMETYPE_DEFAULT);
                $drawing->setHeight(80);
                $drawing->setWorksheet($worksheet);
            } catch (ImageException $exception) {
                $this->logger->info($exception->getMessage());
            }
        }

        $worksheet->getStyle('B3:K17')->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_THICK);
        $worksheet->getStyle('B3:K17')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $worksheet->getCell('C4')->setValue('Distribution No.');
        $worksheet->getCell('C4')->getStyle()->applyFromArray($labelEnStyle);

        $worksheet->getCell('C5')->setValue($this->translator->trans('Distribution No.', [], null, $languageCode));
        $worksheet->getCell('C5')->getStyle()->applyFromArray($labelStyle);

        $worksheet->getCell('D4')->setValue('#' . $assistance->getId());
        $worksheet->getCell('D4')->getStyle()->applyFromArray($userInputStyle);
        $worksheet->mergeCells('D4:D5');

        $worksheet->getCell('E4')->setValue('Location:');
        $worksheet->getCell('E4')->getStyle()->applyFromArray($labelEnStyle);

        $worksheet->getCell('E5')->setValue($this->translator->trans('Location', [], null, $languageCode) . ':');
        $worksheet->getCell('E5')->getStyle()->applyFromArray($labelStyle);

        $worksheet->getCell('F4')->setValue($assistance->getLocation()->getName());
        $worksheet->getCell('F4')->getStyle()->applyFromArray($userInputStyle);
        $worksheet->mergeCells('F4:F5');

        $worksheet->getCell('G4')->setValue('Project & Donor:');
        $worksheet->getCell('G4')->getStyle()->applyFromArray($labelEnStyle);

        $worksheet->getCell('G5')->setValue($this->translator->trans('Project & Donor', [], null, $languageCode) . ':');
        $worksheet->getCell('G5')->getStyle()->applyFromArray($labelStyle);

        $worksheet->getCell('H4')->setValue(self::getProjectsAndDonors($assistance));
        $worksheet->getCell('H4')->getStyle()->applyFromArray($userInputStyle);
        $worksheet->mergeCells('H4:H5');

        $worksheet->getCell('I4')->setValue('Date:');
        $worksheet->getCell('I4')->getStyle()->applyFromArray($labelEnStyle);

        $worksheet->getCell('I5')->setValue($this->translator->trans('Date', [], null, $languageCode) . ':');
        $worksheet->getCell('I5')->getStyle()->applyFromArray($labelStyle);

        $worksheet->getCell('J4')->setValue($assistance->getDateDistribution()->format('Y-m-d'));
        $worksheet->getCell('J4')->getStyle()->applyFromArray($userInputStyle);
        $worksheet->mergeCells('J4:J5');

        $worksheet->getCell('C7')->setValue('Distributed item(s):');
        $worksheet->getCell('C7')->getStyle()->applyFromArray($labelEnStyle);

        $worksheet->getCell('C8')->setValue(
            $this->translator->trans('Distributed item(s)', [], null, $languageCode) . ':'
        );
        $worksheet->getCell('C8')->getStyle()->applyFromArray($labelStyle);

        $worksheet->getCell('D7')->setValue($assistance->getCommodities()->get(0)->getModalityType());
        $worksheet->getCell('D7')->getStyle()->applyFromArray($userInputStyle);
        $worksheet->mergeCells('D7:D8');

        if ($assistance->getCommodities()->get(1)) {
            $worksheet->getCell('E7')->setValue('Distributed item(s):');
            $worksheet->getCell('E7')->getStyle()->applyFromArray($labelEnStyle);

            $worksheet->getCell('E8')->setValue(
                $this->translator->trans('Distributed item(s)', [], null, $languageCode) . ':'
            );
            $worksheet->getCell('E8')->getStyle()->applyFromArray($labelStyle);

            $worksheet->getCell('F7')->setValue($assistance->getCommodities()->get(1)->getModalityType());
            $worksheet->getCell('F7')->getStyle()->applyFromArray($userInputStyle);
            $worksheet->mergeCells('F7:F8');
        }

        if ($assistance->getCommodities()->get(2)) {
            $worksheet->getCell('G7')->setValue('Distributed item(s):');
            $worksheet->getCell('G7')->getStyle()->applyFromArray($labelEnStyle);

            $worksheet->getCell('G8')->setValue(
                $this->translator->trans('Distributed item(s)', [], null, $languageCode) . ':'
            );
            $worksheet->getCell('G8')->getStyle()->applyFromArray($labelStyle);

            $worksheet->getCell('H7')->setValue($assistance->getCommodities()->get(2)->getModalityType());
            $worksheet->getStyle('H7')->applyFromArray($userInputStyle);
            $worksheet->mergeCells('H7:H8');
        }

        $worksheet->getCell('I7')->setValue('Round:');
        $worksheet->getCell('I7')->getStyle()->applyFromArray($labelEnStyle);

        $worksheet->getCell('I8')->setValue($this->translator->trans('Round', [], null, $languageCode) . ':');
        $worksheet->getCell('I8')->getStyle()->applyFromArray($labelStyle);

        $worksheet->getStyle('J7')->applyFromArray($userInputStyle);
        $worksheet->getCell('J7')->setValue(
            $assistance->getRound() ?? $this->translator->trans(
                'N/A',
                [],
                null,
                $languageCode
            )
        );
        $worksheet->mergeCells('J7:J8');

        $worksheet->getCell('C10')->setValue('Validated by:');
        $worksheet->getCell('C10')->getStyle()->applyFromArray($labelEnStyle);

        $worksheet->getCell('C11')->setValue($this->translator->trans('Validated by', [], null, $languageCode) . ':');
        $worksheet->getCell('C11')->getStyle()->applyFromArray($labelStyle);

        $worksheet->getCell('D10')->getStyle()->applyFromArray($userInputStyle);
        $worksheet->getCell('D10')->setValue($this->getValidatedByContent($assistance));
        $worksheet->mergeCells('D10:F11');

        $worksheet->getCell('C13')->setValue("Distributed by:");
        $worksheet->getCell('C14')->setValue("(name, position, signature)");
        $worksheet->getCell('C15')->setValue($this->translator->trans('Distributed by', [], null, $languageCode));
        $worksheet->getCell('C16')->setValue(
            $this->translator->trans('(name, position, signature)', [], null, $languageCode)
        );
        $worksheet->getCell('C13')->getStyle()->applyFromArray($labelEnStyle);
        $worksheet->getCell('C14')->getStyle()->applyFromArray($labelEnStyle);
        $worksheet->getCell('C14')->getStyle()->getFont()->setSize(8);
        $worksheet->getCell('C15')->getStyle()->applyFromArray($labelStyle);
        $worksheet->getCell('C16')->getStyle()->applyFromArray($labelStyle);
        $worksheet->getCell('C16')->getStyle()->getFont()->setSize(8);

        $worksheet->getCell('D13')->setValue($this->getDistributedByContent($assistance));
        $worksheet->mergeCells('D13:F16');
        $worksheet->getStyle('D13')->applyFromArray($userInputStyle);

        $worksheet->getCell('G13')->setValue("Approved by:");
        $worksheet->getCell('G14')->setValue("(name, position, signature)");
        $worksheet->getCell('G15')->setValue($this->translator->trans('Approved by', [], null, $languageCode));
        $worksheet->getCell('G16')->setValue(
            $this->translator->trans('(name, position, signature)', [], null, $languageCode)
        );
        $worksheet->getCell('G13')->getStyle()->applyFromArray($labelEnStyle);
        $worksheet->getCell('G14')->getStyle()->applyFromArray($labelEnStyle);
        $worksheet->getCell('G14')->getStyle()->getFont()->setSize(8);
        $worksheet->getCell('G15')->getStyle()->applyFromArray($labelStyle);
        $worksheet->getCell('G16')->getStyle()->applyFromArray($labelStyle);
        $worksheet->getCell('G16')->getStyle()->getFont()->setSize(8);

        $worksheet->getStyle('H13')->applyFromArray($userInputStyle);
        $worksheet->getCell('H13')->setValue($this->getApprovedByContent($assistance));
        $worksheet->mergeCells('H13:J16');

        $worksheet->getCell('B19')->setValue(
            'The below listed person confirm by their signature of this distribution list that they obtained and accepted the donation of the below specified items from People in Need.'
        );
        $worksheet->mergeCells('B19:K19');

        $worksheet->getCell('B20')->setValue(
            $this->translator->trans(
                'The below listed person confirm by their signature of this Distribution List that they obtained and accepted the donation of the below specified items from People in Need.',
                [],
                null,
                $languageCode
            )
        );
        $worksheet->getStyle('B20')->getFont()->setItalic(true);
        $worksheet->getStyle('B19:K20')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $worksheet->mergeCells('B20:K20');

        $worksheet->getCell('B22')->setValue(
            self::GDPR_TEXT_1 .
            "\n" .
            self::GDPR_TEXT_2
        );
        $worksheet->mergeCells('B22:K22');
        $worksheet->getStyle('B22')->getFont()->setSize(8);
        $worksheet->getStyle('B22')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $worksheet->getStyle('B22')->getAlignment()->setWrapText(true);

        $worksheet->getCell('B23')->setValue(
            self::getStringWithoutNewLineCharacters(
                $this->translator->trans('GDPR_Distribution_protocol_text_1', [], null, $languageCode)
            ) .
            "\n" .
            self::getStringWithoutNewLineCharacters(
                $this->translator->trans('GDPR_Distribution_protocol_text_2', [], null, $languageCode)
            )
        );
        $worksheet->mergeCells('B23:K23');
        $worksheet->getStyle('B23')->getFont()->setItalic(true);
        $worksheet->getStyle('B23')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $worksheet->getStyle('B23')->getAlignment()->setWrapText(true);
        $worksheet->getStyle('B23')->getFont()->setSize(8);
    }

    private function getDistributedByContent(Assistance $assistance): string
    {
        if (!$assistance->isSmartcardDistribution()) {
            return '';
        }

        $rows = [];

        $countDistributedByVendor = $this->reliefPackageRepository->countByAssistanceDistributedByVendors($assistance);

        dump($countDistributedByVendor);

        if ($assistance->isRemoteDistributionAllowed() && $countDistributedByVendor > 0) {
            $rows[] = 'Vendors';
        }

        $distributedReliefPackages = $this
            ->reliefPackageRepository
            ->findByAssistanceDistributedByFieldApp($assistance);

        $distributedByUsers = [];
        foreach ($distributedReliefPackages as $reliefPackage) {
            if (!array_key_exists($reliefPackage->getDistributedBy()->getId(), $distributedByUsers)) {
                $distributedByUsers[$reliefPackage->getDistributedBy()->getId()] = $reliefPackage->getDistributedBy();
            }
        }

        foreach ($distributedByUsers as $user) {
            $rows[] = $this->getUserInformationString($user);
        }

        return trim(implode("\n", $rows));
    }

    private function getValidatedByContent(Assistance $assistance): string
    {
        if (!$assistance->isSmartcardDistribution()) {
            return '';
        }

        if (!$assistance->isValidated()) {
            return '';
        }

        return $this->getUserInformationString($assistance->getValidatedBy());
    }

    private function getApprovedByContent(Assistance $assistance): string
    {
        if (!$assistance->isSmartcardDistribution()) {
            return '';
        }

        if (!$assistance->getCompleted()) {
            return '';
        }

        return $this->getUserInformationString($assistance->getClosedBy());
    }

    private function getUserInformationString(User $user): string
    {
        $informationString = '';

        if ($user->getFirstName() !== null) {
            $informationString .= $user->getFirstName();
        }

        if ($user->getLastName() !== null) {
            $informationString .= ' ' .$user->getLastName();
        }

        if ($user->getPosition() !== null) {
            $informationString .= ' (' . $user->getPosition() . ')';
        }

        if ($informationString === '') {
            $informationString = $user->getEmail();
        }

        return trim($informationString);
    }

    /**
     * @throws Exception
     */
    private function buildBody(Worksheet $worksheet, Assistance $assistance, string $languageCode): void
    {
        $rowStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'wrapText' => true,
            ],
        ];

        $worksheet->getCell('B25')->setValue('No.');
        $worksheet->getCell('C25')->setValue('First Name');
        $worksheet->getCell('D25')->setValue('Second Name');
        $worksheet->getCell('E25')->setValue('ID No.');
        $worksheet->getCell('F25')->setValue('Phone No.');
        $worksheet->getCell('G25')->setValue('Proxy First Name');
        $worksheet->getCell('H25')->setValue('Proxy Second Name');
        $worksheet->getCell('I25')->setValue('Proxy ID No.');
        $worksheet->getCell('J25')->setValue('Distributed Item(s), Unit, Amount per beneficiary');
        $worksheet->getStyle('B25:K25')->applyFromArray($rowStyle);
        $worksheet->getStyle('B25:K25')->getFont()->setBold(true);
        $worksheet->getRowDimension(25)->setRowHeight(42.00);

        if ($this->shouldDistributionContainDate($assistance)) {
            $worksheet->getCell('K25')->setValue('Distributed');
            $worksheet->setCellValue('K26', $this->translator->trans('Distributed', [], null, $languageCode));
            $this->smartCardDeposits = $this->smartcardDepositService->getDepositsForDistributionBeneficiaries(
                $assistance->getDistributionBeneficiaries()->toArray()
            );
        } else {
            $worksheet->getCell('K25')->setValue('Signature / Time-stamp');
            $worksheet->setCellValue(
                'K26',
                $this->translator->trans('Signature / Time-stamp', [], null, $languageCode)
            );
        }
        $worksheet->setCellValue('B26', $this->translator->trans('No.', [], null, $languageCode));
        $worksheet->setCellValue('C26', $this->translator->trans('First Name', [], null, $languageCode));
        $worksheet->setCellValue('D26', $this->translator->trans('Second Name', [], null, $languageCode));
        $worksheet->setCellValue('E26', $this->translator->trans('ID No.', [], null, $languageCode));
        $worksheet->setCellValue('F26', $this->translator->trans('Phone No.', [], null, $languageCode));
        $worksheet->setCellValue('H26', $this->translator->trans('Proxy Second Name', [], null, $languageCode));
        $worksheet->setCellValue('G26', $this->translator->trans('Proxy First Name', [], null, $languageCode));
        $worksheet->setCellValue('I26', $this->translator->trans('Proxy ID No.', [], null, $languageCode));
        $worksheet->setCellValue(
            'J26',
            $this->translator->trans('Distributed Item(s), Unit, Amount per beneficiary', [], null, $languageCode)
        );
        $worksheet->getStyle('B23:K26')->applyFromArray($rowStyle);
        $worksheet->getStyle('B26:K26')->getFont()->setItalic(true);
        $worksheet->getRowDimension(26)->setRowHeight(42.00);

        $worksheet->getStyle('B25:K25')->getBorders()
            ->getTop()
            ->setBorderStyle(Border::BORDER_THICK);
        $worksheet->getStyle('B26:K26')->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_THICK);
        $worksheet->getStyle('B25:K26')->getBorders()
            ->getLeft()
            ->setBorderStyle(Border::BORDER_THICK);
        $worksheet->getStyle('B25:K26')->getBorders()
            ->getRight()
            ->setBorderStyle(Border::BORDER_THICK);

        /** @var AssistanceBeneficiary[] $assistanceBeneficiaries */
        $assistanceBeneficiaries = $assistance->getDistributionBeneficiaries()->toArray();

        usort($assistanceBeneficiaries, function (AssistanceBeneficiary $a, AssistanceBeneficiary $b) {
            if (strcmp(strtolower($a->getBeneficiary()->getPerson()->getLocalFamilyName()), strtolower($b->getBeneficiary()->getPerson()->getLocalFamilyName())) === 0) {
                return strcmp(strtolower($a->getBeneficiary()->getPerson()->getLocalGivenName()), strtolower($b->getBeneficiary()->getPerson()->getLocalGivenName()));
            }

            return strcmp(strtolower($a->getBeneficiary()->getPerson()->getLocalFamilyName()), strtolower($b->getBeneficiary()->getPerson()->getLocalFamilyName()));
        });

        $rowNumber = 27;
        $rowIndex = 1;
        foreach ($assistanceBeneficiaries as $distributionBeneficiary) {
            if (!$distributionBeneficiary->getRemoved()) {
                $rowNumber = $this->createBeneficiaryRow(
                    $worksheet,
                    $distributionBeneficiary,
                    $rowNumber,
                    $rowIndex++,
                    $rowStyle,
                    $this->shouldDistributionContainDate($assistance)
                );
            }
        }
    }

    /**
     * @throws Exception
     */
    private function createBeneficiaryRow(
        Worksheet $worksheet,
        AssistanceBeneficiary $distributionBeneficiary,
        $rowNumber,
        $id,
        $rowStyle,
        bool $shouldContainDate
    ): int {
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

        $worksheet->setCellValue('B' . $rowNumber, $id);
        $worksheet->setCellValue('C' . $rowNumber, $person->getLocalGivenName());
        $worksheet->setCellValue('D' . $rowNumber, $person->getLocalFamilyName());
        $worksheet->setCellValueExplicit('E' . $rowNumber, self::getNationalId($person), DataType::TYPE_STRING);
        $worksheet->setCellValueExplicit('F' . $rowNumber, self::getPhone($person), DataType::TYPE_STRING);
        $worksheet->setCellValue('G' . $rowNumber, null);
        $worksheet->setCellValue('H' . $rowNumber, null);
        $worksheet->setCellValue('I' . $rowNumber, self::getProxyPhone($person));
        $worksheet->setCellValue(
            'J' . $rowNumber,
            $distributionBeneficiary->getRemoved() ? '' : self::getDistributedItems($distributionBeneficiary)
        );
        $worksheet->getStyle('B' . $rowNumber . ':K' . $rowNumber)->applyFromArray($rowStyle);
        $worksheet->getRowDimension($rowNumber)->setRowHeight(42.00 * max($person->getNationalIds()->count(), 1));

        if ($shouldContainDate) {
            $worksheet->setCellValue('K' . $rowNumber, $this->getDistributionDateTime($distributionBeneficiary));
        }

        return $rowNumber + 1;
    }

    private static function getProjectsAndDonors(Assistance $assistance): string
    {
        $donors = [];
        foreach ($assistance->getProject()->getDonors() as $donor) {
            $donors[] = $donor->getShortname();
        }

        return [] === $donors ? $assistance->getProject()->getName() : $assistance->getProject()->getName(
        ) . ' & ' . implode(', ', $donors);
    }

    /**
     * @throws Exception
     */
    private static function getNationalId(Person $person): ?string
    {
        $ids = [];
        foreach ($person->getNationalIds() as $nationalId) {
            $ids[] = "{$nationalId->getIdNumber()} ({$nationalId->getIdType()})";
        }

        return join(PHP_EOL, $ids);
    }

    private static function getPhone(Person $person): ?string
    {
        foreach ($person->getPhones() as $p) {
            if (!$p->getProxy()) {
                return $p->getPrefix() . ' ' . self::splitStringToGroupsOfThree($p->getNumber());
            }
        }

        return null;
    }

    private static function splitStringToGroupsOfThree(string $phoneNumber): string
    {
        $splitPhoneNumber = str_split(str_replace(' ', '', $phoneNumber), 3);

        return implode(' ', $splitPhoneNumber);
    }

    private static function getProxyPhone(Person $person): ?string
    {
        foreach ($person->getPhones() as $p) {
            if ($p->getProxy()) {
                return $p->getPrefix() . ' ' . self::splitStringToGroupsOfThree($p->getNumber());
            }
        }

        return null;
    }

    private static function getDistributedItems(AssistanceBeneficiary $assistanceBeneficiary): ?string
    {
        $result = [];

        foreach ($assistanceBeneficiary->getSmartcardDeposits() as $deposit) {
            $result[] = 'Smartcard deposit: ' . $deposit->getValue() . ' ' . $deposit->getSmartcard()->getCurrency();
        }

        foreach ($assistanceBeneficiary->getReliefPackagesNotInStates([ReliefPackageState::CANCELED]) as $relief) {
            $result[] = $relief->getModalityType() . ', ' . $relief->getAmountToDistribute() . ' ' . $relief->getUnit();
        }

        return implode("\n", $result);
    }

    private static function getStringWithoutNewLineCharacters(string $string): string
    {
        return preg_replace('/\s+/', ' ', trim($string));
    }

    private function shouldDistributionContainDate(Assistance $assistance): bool
    {
        return $assistance->isRemoteDistributionAllowed() === true;
    }

    private function getDistributionDateTime(AssistanceBeneficiary $distributionBeneficiary): string
    {
        $deposits = array_filter($this->smartCardDeposits, fn($smartcardDeposit) => $smartcardDeposit->getReliefPackage()->getAssistanceBeneficiary()->getId(
        ) === $distributionBeneficiary->getId());

        if (empty($deposits)) {
            return "";
        }

        return implode(
            "\n",
            array_map(fn($deposit) => $deposit->getDistributedAt()->format('d. m. Y H:i'), $deposits)
        );
    }
}
