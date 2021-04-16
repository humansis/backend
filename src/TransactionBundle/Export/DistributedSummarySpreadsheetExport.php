<?php

declare(strict_types=1);

namespace TransactionBundle\Export;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Entity\Commodity;
use DistributionBundle\Entity\GeneralReliefItem;
use DistributionBundle\Enum\AssistanceType;
use NewApiBundle\Component\Country\Countries;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use ProjectBundle\Entity\Project;
use Symfony\Component\Translation\TranslatorInterface;
use TransactionBundle\Entity\Transaction;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\Entity\Voucher;

class DistributedSummarySpreadsheetExport
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var Countries */
    private $countries;

    public function __construct(TranslatorInterface $translator, Countries $countries)
    {
        $this->translator = $translator;
        $this->countries = $countries;
    }

    public function export(Project $project, string $filetype)
    {
        if (!in_array($filetype, ['ods', 'xlsx', 'csv'], true)) {
            throw new \InvalidArgumentException('Invalid file type. Expected one of ods, xlsx, csv. '.$filetype.' given.');
        }

        $filename = sys_get_temp_dir().'/summary.'.$filetype;

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $this->build($worksheet, $project);

        $writer = IOFactory::createWriter($spreadsheet, ucfirst($filetype));
        $writer->save($filename);

        return $filename;
    }

    private function build(Worksheet $worksheet, Project $project)
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
        $worksheet->getColumnDimension('R')->setWidth(28.997);
        $worksheet->getRowDimension(1)->setRowHeight(28.705);
        $worksheet->setRightToLeft('right-to-left' === \Punic\Misc::getCharacterOrder($this->translator->getLocale()));
        $worksheet->getStyle('A1:R1')->applyFromArray([
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

        $country = $this->countries->getCountry($project->getIso3());
        $dateFormatter = new \IntlDateFormatter($this->translator->getLocale(), \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE);

        $worksheet->setCellValue('A1', $this->translator->trans('Beneficiary ID'));
        $worksheet->setCellValue('B1', $this->translator->trans('Beneficiary Type'));
        $worksheet->setCellValue('C1', $this->translator->trans('Beneficiary First Name (local)'));
        $worksheet->setCellValue('D1', $this->translator->trans('Beneficiary Family Name (local)'));
        $worksheet->setCellValue('E1', $this->translator->trans('ID Number'));
        $worksheet->setCellValue('F1', $this->translator->trans('Phone'));
        $worksheet->setCellValue('G1', $this->translator->trans('Distribution Name'));
        $worksheet->setCellValue('H1', $this->translator->trans($country->getAdm1Name()));
        $worksheet->setCellValue('I1', $this->translator->trans($country->getAdm2Name()));
        $worksheet->setCellValue('J1', $this->translator->trans($country->getAdm3Name()));
        $worksheet->setCellValue('K1', $this->translator->trans($country->getAdm4Name()));
        $worksheet->setCellValue('L1', $this->translator->trans('Date of Distribution'));
        $worksheet->setCellValue('M1', $this->translator->trans('Commodity Type'));
        $worksheet->setCellValue('N1', $this->translator->trans('Carrier No.'));
        $worksheet->setCellValue('O1', $this->translator->trans('Quantity'));
        $worksheet->setCellValue('P1', $this->translator->trans('Amount Distributed'));
        $worksheet->setCellValue('Q1', $this->translator->trans('Unit'));
        $worksheet->setCellValue('R1', $this->translator->trans('Field Officer Email'));

        $i = 1;
        /** @var Assistance $assistance */
        foreach ($project->getDistributions() as $assistance) {
            if (AssistanceType::ACTIVITY === $assistance->getAssistanceType()) {
                continue;
            }

            /** @var Commodity $commodity */
            $commodity = $assistance->getCommodities()->first();

            /** @var AssistanceBeneficiary $assistanceBeneficiary */
            foreach ($assistance->getDistributionBeneficiaries() as $assistanceBeneficiary) {
                if ($assistanceBeneficiary->getSmartcardDeposits()->isEmpty() &&
                    $assistanceBeneficiary->getTransactions()->isEmpty() &&
                    $assistanceBeneficiary->getBooklets()->isEmpty() &&
                    $assistanceBeneficiary->getGeneralReliefs()->isEmpty()) {
                    continue;
                }

                /** @var Beneficiary $beneficiary */
                $beneficiary = $assistanceBeneficiary->getBeneficiary();

                $carrier = $dateOfDistribution = $fieldOfficerEmail = $amount = null;
                switch ($commodity->getModalityType()) {
                    case 'Smartcard':
                        if (!$assistanceBeneficiary->getSmartcardDistributedAt()) {
                            continue 2;
                        }

                        $carrier = $beneficiary->getSmartcard();
                        $dateOfDistribution = $dateFormatter->format($assistanceBeneficiary->getSmartcardDistributedAt());
                        $fieldOfficerEmail = $assistanceBeneficiary->getSmartcardDeposits()->first()->getDepositor()->getEmail();
                        $amount = array_reduce($assistanceBeneficiary->getSmartcardDeposits()->toArray(), function ($ax, SmartcardDeposit $dx) {
                            return $ax + $dx->getValue();
                        }, 0);
                        break;

                    case 'QR Code Voucher':
                        /** @var Booklet $booklet */
                        $booklet = $assistanceBeneficiary->getBooklets()->first();
                        if (Booklet::USED !== $booklet->getStatus() && Booklet::DISTRIBUTED !== $booklet->getStatus()) {
                            continue 2;
                        }

                        $carrier = $booklet->getCode();
                        $dateOfDistribution = $booklet->getUsedAt() ? $dateFormatter->format($booklet->getUsedAt()) : null;
                        $amount = array_reduce($booklet->getVouchers()->toArray(), function ($ax, Voucher $dx) {
                            return $ax + $dx->getValue();
                        }, 0);
                        break;

                    case 'Mobile Money':
                        $transactions = $assistanceBeneficiary->getTransactions()->filter(function (Transaction $transaction) {
                            return Transaction::SUCCESS === $transaction->getTransactionStatus();
                        });
                        if ($transactions->isEmpty()) {
                            continue 2;
                        }

                        $dateOfDistribution = $dateFormatter->format($transactions->last()->getPickupDate());
                        $fieldOfficerEmail = $transactions->first()->getSentBy()->getEmail();
                        $amount = array_reduce($transactions->toArray(), function ($ax, Transaction $dx) {
                            preg_match('~\d+(\.\d+)?~', $dx->getAmountSent(), $matches);
                            return $ax + $matches[0];
                        }, 0);
                        break;

                    default:
                        /** @var GeneralReliefItem $generalRelief */
                        $generalRelief = $assistanceBeneficiary->getGeneralReliefs()->first();
                        if (!$generalRelief->getDistributedAt()) {
                            continue 2;
                        }

                        $dateOfDistribution = $dateFormatter->format($generalRelief->getDistributedAt());
                        $amount = $commodity->getValue();
                        break;
                }

                $i++;
                $worksheet->setCellValue('A'.$i, $beneficiary->getId());
                $worksheet->setCellValue('B'.$i, $beneficiary->isHead() ? $this->translator->trans('Household') : $this->translator->trans('Individual'));
                $worksheet->setCellValue('C'.$i, $beneficiary->getLocalGivenName());
                $worksheet->setCellValue('D'.$i, $beneficiary->getLocalFamilyName());
                $worksheet->setCellValue('E'.$i, self::nationalId($beneficiary) ?? $this->translator->trans('N/A'));
                $worksheet->setCellValue('F'.$i, self::phone($beneficiary) ?? $this->translator->trans('N/A'));
                $worksheet->setCellValue('G'.$i, $assistance->getName());
                $worksheet->setCellValue('H'.$i, self::adms($assistance)[0]);
                $worksheet->setCellValue('I'.$i, self::adms($assistance)[1]);
                $worksheet->setCellValue('J'.$i, self::adms($assistance)[2]);
                $worksheet->setCellValue('K'.$i, self::adms($assistance)[3]);
                $worksheet->setCellValue('L'.$i, $dateOfDistribution ?? $this->translator->trans('N/A'));
                $worksheet->setCellValue('M'.$i, $commodity->getModalityType()->getName());
                $worksheet->setCellValue('N'.$i, $carrier ?? $this->translator->trans('N/A'));
                $worksheet->setCellValue('O'.$i, $commodity->getValue());
                $worksheet->setCellValue('P'.$i, $amount);
                $worksheet->setCellValue('Q'.$i, $commodity->getUnit());
                $worksheet->setCellValue('R'.$i, $fieldOfficerEmail ?? $this->translator->trans('N/A'));
            }
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
            if (NationalId::TYPE_NATIONAL_ID === $nationalId->getIdType()) {
                return $nationalId->getIdNumber();
            }
        }

        return null;
    }

    private static function adms(Assistance $assistance): array
    {
        $adm = $assistance->getLocation()->getAdm();
        if ($adm instanceof Adm1) {
            return [$adm->getName(), null, null, null];
        }
        if ($adm instanceof Adm2) {
            return [$adm->getAdm1()->getName(), $adm->getName(), null, null];
        }
        if ($adm instanceof Adm3) {
            return [$adm->getAdm2()->getAdm1()->getName(), $adm->getAdm2()->getName(), $adm->getName(), null];
        }
        if ($adm instanceof Adm4) {
            return [$adm->getAdm3()->getAdm2()->getAdm1()->getName(), $adm->getAdm3()->getAdm2()->getName(), $adm->getAdm3()->getName(), $adm->getName()];
        }
    }
}

