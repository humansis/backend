<?php

declare(strict_types=1);

namespace Utils;

use Enum\AssistanceTargetType;
use Enum\PersonGender;
use Repository\AssistanceRepository;
use Repository\BeneficiaryRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjectAssistancesTransformData
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var BeneficiaryRepository */
    private $beneficiaryRepository;

    /** @var AssistanceRepository */
    private $assistanceRepository;

    public function __construct(
        TranslatorInterface $translator,
        BeneficiaryRepository $beneficiaryRepository,
        AssistanceRepository $assistanceRepository
    ) {
        $this->translator = $translator;
        $this->beneficiaryRepository = $beneficiaryRepository;
        $this->assistanceRepository = $assistanceRepository;
    }

    /**
     * Returns an array representation of Distributions in order to prepare the export
     *
     * @param $project
     * @param $assistances
     *
     * @return array
     */
    public function transformData($project, $assistances): array
    {
        $exportableTable = [];

        $donors = implode(
            ', ',
            array_map(function ($donor) {
                return $donor->getShortname();
            }, $project->getDonors()->toArray())
        );

        foreach ($assistances as $assistance) {
            $idps = $this->beneficiaryRepository->countByResidencyStatus($assistance, "IDP");
            $residents = $this->beneficiaryRepository->countByResidencyStatus($assistance, "resident");
            $maleHHH = $this->beneficiaryRepository->countHouseholdHeadsByGender($assistance, PersonGender::MALE);
            $femaleHHH = $this->beneficiaryRepository->countHouseholdHeadsByGender($assistance, PersonGender::FEMALE);
            $maleChildrenUnder23month = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                1,
                0,
                2,
                $assistance->getDateDistribution()
            );
            $femaleChildrenUnder23month = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                0,
                0,
                2,
                $assistance->getDateDistribution()
            );
            $maleChildrenUnder5years = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                1,
                2,
                6,
                $assistance->getDateDistribution()
            );
            $femaleChildrenUnder5years = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                0,
                2,
                6,
                $assistance->getDateDistribution()
            );
            $maleUnder17years = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                1,
                6,
                18,
                $assistance->getDateDistribution()
            );
            $femaleUnder17years = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                0,
                6,
                18,
                $assistance->getDateDistribution()
            );
            $maleUnder59years = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                1,
                18,
                60,
                $assistance->getDateDistribution()
            );
            $femaleUnder59years = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                0,
                18,
                60,
                $assistance->getDateDistribution()
            );
            $maleOver60years = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                1,
                60,
                200,
                $assistance->getDateDistribution()
            );
            $femaleOver60years = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                0,
                60,
                200,
                $assistance->getDateDistribution()
            );
            $maleTotal = $maleChildrenUnder23month + $maleChildrenUnder5years + $maleUnder17years + $maleUnder59years + $maleOver60years;
            $femaleTotal = $femaleChildrenUnder23month + $femaleChildrenUnder5years + $femaleUnder17years + $femaleUnder59years + $femaleOver60years;
            $noFamilies = $assistance->getTargetType(
            ) === AssistanceTargetType::INDIVIDUAL ? ($maleTotal + $femaleTotal) : ($maleHHH + $femaleHHH);
            $familySize = $assistance->getTargetType(
            ) === AssistanceTargetType::HOUSEHOLD && $noFamilies ? ($maleTotal + $femaleTotal) / $noFamilies : null;
            $modalityType = $assistance->getCommodities()[0]->getModalityType();
            $beneficiaryServed = $this->assistanceRepository->getNoServed($assistance->getId(), $modalityType);

            $commodityNames = implode(
                ', ',
                array_map(
                    function ($commodity) {
                        return $commodity->getModalityType();
                    },
                    $assistance->getCommodities()->toArray()
                )
            );
            $commodityUnit = implode(
                ', ',
                array_map(
                    function ($commodity) {
                        return $commodity->getUnit();
                    },
                    $assistance->getCommodities()->toArray()
                )
            );
            $numberOfUnits = implode(
                ', ',
                array_map(
                    function ($commodity) {
                        return $commodity->getValue();
                    },
                    $assistance->getCommodities()->toArray()
                )
            );

            $totalAmount = implode(
                ', ',
                array_map(
                    function ($commodity) use ($noFamilies) {
                        return $commodity->getValue() * $noFamilies . ' ' . $commodity->getUnit();
                    },
                    $assistance->getCommodities()->toArray()
                )
            );

            $exportableTable[] = [
                $this->translator->trans("Navi/Elo number") => $assistance->getProject()->getInternalId() ?? " ",
                $this->translator->trans("DISTR. NO.") => $assistance->getId(),
                $this->translator->trans("Distributed by") => " ",
                $this->translator->trans("Round") => ($assistance->getRound() === null ? $this->translator->trans(
                    "N/A"
                ) : $assistance->getRound()),
                $this->translator->trans("Donor") => $donors,
                $this->translator->trans("Starting Date") => $assistance->getDateDistribution(),
                $this->translator->trans("Ending Date") => $assistance->getCompleted() ? $assistance->getUpdatedOn(
                ) : " - ",
                $this->translator->trans("Governorate") => $assistance->getLocation()->getAdm1Name(),
                $this->translator->trans("District") => $assistance->getLocation()->getAdm2Name(),
                $this->translator->trans("Sub-District") => $assistance->getLocation()->getAdm3Name(),
                $this->translator->trans("Town, Village") => $assistance->getLocation()->getAdm4Name(),
                $this->translator->trans("Location = School/Camp") => " ",
                $this->translator->trans("Neighbourhood (Camp Name)") => " ",
                $this->translator->trans("Latitude") => " ",
                $this->translator->trans("Longitude") => " ",
                // $this->translator->trans("Location Code") => $distribution->getLocation()->getCode(),
                $this->translator->trans("Activity (Modality)") => $commodityNames,
                $this->translator->trans("UNIT") => $commodityUnit,
                $this->translator->trans("Nº Of Units") => $numberOfUnits,
                $this->translator->trans("Amount (USD/SYP)") => " ",
                $this->translator->trans("Total Amount") => $totalAmount,
                $this->translator->trans("Bebelac Type") => " ",
                $this->translator->trans("Water\nNº of 1.5 bottles ") => " ",
                $this->translator->trans("Bebelac kg") => " ",
                $this->translator->trans("Nappies Pack") => " ",
                $this->translator->trans("IDPs") => $idps,
                $this->translator->trans("Residents") => $residents,
                $this->translator->trans("Nº FAMILIES") => $noFamilies,
                $this->translator->trans("FEMALE\nHead of Family gender") => $femaleHHH,
                $this->translator->trans("MALE\nHead of Family gender") => $maleHHH,
                /*
                * Male and Female children from 0 to 17 months
                */
                $this->translator->trans("Children\n0-23 months\nMale") => $maleChildrenUnder23month,
                $this->translator->trans("Children\n0-23 months\nFemale") => $femaleChildrenUnder23month,
                //$this->translator->trans("Children\n2-5") => $childrenUnder5years
                $this->translator->trans("Children\n2-5\nMale") => $maleChildrenUnder5years,
                $this->translator->trans("Children\n2-5\nFemale") => $femaleChildrenUnder5years,
                $this->translator->trans("Males\n6-17") => $maleUnder17years,
                $this->translator->trans("Females\n6-17") => $femaleUnder17years,
                $this->translator->trans("Males\n18-59") => $maleUnder59years,
                $this->translator->trans("Females\n18-59") => $femaleUnder59years,
                $this->translator->trans("Males\n60+") => $maleOver60years,
                $this->translator->trans("Females\n60+") => $femaleOver60years,
                $this->translator->trans("Total\nMales") => $maleTotal,
                $this->translator->trans("Total\nFemales") => $femaleTotal,
                $this->translator->trans("Individ. Benef.\nServed") => $beneficiaryServed,
                $this->translator->trans("Family\nSize") => $familySize,
            ];
        }

        return $exportableTable;
    }
}
