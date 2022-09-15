<?php

namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\AbstractBeneficiary;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Exception\CsvParserException;
use CommonBundle\Pagination\Paginator;
use CommonBundle\Utils\LocationService;
use DateTime;
use DateTimeInterface;
use DistributionBundle\DTO\VulnerabilityScore;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Entity\ModalityType;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Enum\AssistanceType;
use DistributionBundle\Repository\AssistanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Exception;
use NewApiBundle\Component\Assistance\AssistanceFactory;
use NewApiBundle\Component\Assistance\Domain\Assistance as AssistanceDomain;
use NewApiBundle\Component\Assistance\SelectionCriteriaFactory;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Enum\CacheTarget;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\InputType\Assistance\SelectionCriterionInputType;
use NewApiBundle\InputType\Assistance\UpdateAssistanceInputType;
use NewApiBundle\InputType\AssistanceCreateInputType;
use NewApiBundle\Repository\ScoringBlueprintRepository;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Voucher;

/**
 * Class AssistanceService
 * @package DistributionBundle\Utils
 */
class AssistanceService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var LocationService $locationService */
    private $locationService;

    /** @var CommodityService $commodityService */
    private $commodityService;

    /** @var CriteriaAssistanceService $criteriaAssistanceService */
    private $criteriaAssistanceService;

    /** @var ContainerInterface $container */
    private $container;

    /** @var CacheInterface */
    private $cache;

    /** @var AssistanceFactory */
    private $assistanceFactory;

    /** @var AssistanceRepository */
    private $assistanceRepository;

    /** @var SelectionCriteriaFactory */
    private $selectionCriteriaFactory;

    /** @var ScoringBlueprintRepository */
    private $scoringBlueprintRepository;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * AssistanceService constructor.
     *
     * @param EntityManagerInterface    $entityManager
     * @param Serializer                $serializer
     * @param ValidatorInterface        $validator
     * @param LocationService           $locationService
     * @param CommodityService          $commodityService
     * @param CriteriaAssistanceService $criteriaAssistanceService
     * @param ContainerInterface        $container
     * @param FilesystemAdapter         $cache
     * @param AssistanceFactory         $assistanceFactory
     * @param AssistanceRepository      $assistanceRepository
     * @param SelectionCriteriaFactory  $selectionCriteriaFactory
     */
    public function __construct(
        EntityManagerInterface    $entityManager,
        Serializer                $serializer,
        ValidatorInterface        $validator,
        LocationService           $locationService,
        CommodityService          $commodityService,
        CriteriaAssistanceService $criteriaAssistanceService,
        ContainerInterface        $container,
        Environment               $twig,
        CacheInterface            $cache,
        AssistanceFactory         $assistanceFactory,
        AssistanceRepository      $assistanceRepository,
        SelectionCriteriaFactory  $selectionCriteriaFactory,
        TranslatorInterface $translator
    ) {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->locationService = $locationService;
        $this->commodityService = $commodityService;
        $this->criteriaAssistanceService = $criteriaAssistanceService;
        $this->container = $container;
        $this->cache = $cache;
        $this->assistanceFactory = $assistanceFactory;
        $this->assistanceRepository = $assistanceRepository;
        $this->selectionCriteriaFactory = $selectionCriteriaFactory;
        $this->twig = $twig;
        $this->translator = $translator;
    }

    /**
     * @param Assistance                $assistanceRoot
     * @param UpdateAssistanceInputType $updateAssistanceInputType
     * @param User                      $user
     *
     * @return AssistanceDomain
     */
    public function update(
        Assistance                $assistanceRoot,
        UpdateAssistanceInputType $updateAssistanceInputType,
        User                      $user
    ): AssistanceDomain {
        $assistance = $this->assistanceFactory->hydrate($assistanceRoot);
        if ($updateAssistanceInputType->hasValidated()) {
            if ($updateAssistanceInputType->getValidated()) {
                $assistance->validate($user);
            } else {
                $assistance->unvalidate();
            }
        }
        if ($updateAssistanceInputType->isCompleted()) {
            $assistance->complete();
        }
        if ($updateAssistanceInputType->hasDateDistribution()) {
            $this->updateDateDistribution($assistanceRoot, $updateAssistanceInputType->getDateDistribution());
        }
        if ($updateAssistanceInputType->hasDateExpiration()) {
            $this->updateDateExpiration($assistanceRoot, $updateAssistanceInputType->getDateExpiration());
        }
        if ($updateAssistanceInputType->hasRound()) {
            $this->updateRound($assistanceRoot, $updateAssistanceInputType->getRound());
        }
        if ($updateAssistanceInputType->hasNote()) {
            $this->updateNote($assistanceRoot, $updateAssistanceInputType->getNote());
        }

        $this->assistanceRepository->save($assistance);

        return $assistance;
    }

    /**
     * @param Assistance $assistanceRoot
     * @param User       $user
     *
     * @deprecated use Assistance::validate instead
     */
    public function validateDistribution(Assistance $assistanceRoot, User $user)
    {
        $assistance = $this->assistanceFactory->hydrate($assistanceRoot);
        $assistance->validate($user);
        $this->assistanceRepository->save($assistance);
    }

    // TODO: presunout do ABNF
    public function findByCriteria(AssistanceCreateInputType $inputType, Pagination $pagination)
    {
        $project = $this->em->getRepository(Project::class)->find($inputType->getProjectId());
        if (!$project) {
            throw new EntityNotFoundException('Project #'.$inputType->getProjectId().' does not exists.');
        }

        $selectionGroups = $this->selectionCriteriaFactory->createGroups($inputType->getSelectionCriteria());
        $result = $this->criteriaAssistanceService->load($selectionGroups, $project, $inputType->getTarget(), $inputType->getSector(), $inputType->getSubsector(), $inputType->getThreshold(), false,  $inputType->getScoringBlueprintId());
        $ids = array_keys($result['finalArray']);
        $count = count($ids);

        $ids = array_slice($ids, $pagination->getOffset(), $pagination->getSize());

        $beneficiaries = $this->em->getRepository(AbstractBeneficiary::class)->findBy(['id' => $ids]);

        return new Paginator($beneficiaries, $count);
    }

    /**
     * @param AssistanceCreateInputType $inputType
     * @param Pagination                $pagination
     *
     * @return Paginator|VulnerabilityScore[]
     * @throws EntityNotFoundException
     * @throws CsvParserException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    public function findVulnerabilityScores(AssistanceCreateInputType $inputType, Pagination $pagination)
    {
        $project = $this->em->getRepository(Project::class)->find($inputType->getProjectId());
        if (!$project) {
            throw new EntityNotFoundException('Project #'.$inputType->getProjectId().' does not exists.');
        }

        $selectionGroups = $this->selectionCriteriaFactory->createGroups($inputType->getSelectionCriteria());
        $result = $this->criteriaAssistanceService->load($selectionGroups, $project, $inputType->getTarget(), $inputType->getSector(), $inputType->getSubsector(), $inputType->getThreshold(), false, $inputType->getScoringBlueprintId());
        $ids = array_keys($result['finalArray']);
        $count = count($ids);

        $ids = array_slice($ids, $pagination->getOffset(), $pagination->getSize());

        $list = [];
        foreach ($ids as $id) {
            $beneficiary = $this->em->getRepository(AbstractBeneficiary::class)->find($id);
            $list[] = new VulnerabilityScore($beneficiary, $result['finalArray'][$id]);
        }

        return new Paginator($list, $count);
    }

    /**
     * @deprecated use AssistanceFactory::create instead
     * Create a distribution
     *
     * @param $countryISO3
     * @param array $distributionArray
     * @return array
     * @throws ValidationException
     */
    public function createFromArray($countryISO3, array $distributionArray)
    {
        $location = $distributionArray['location'];
        unset($distributionArray['location']);

        $selectionCriteriaGroup = $distributionArray['selection_criteria'] ?? null;
        unset($distributionArray['selection_criteria']);

        $sector = $distributionArray['sector'];
        unset($distributionArray['sector']);

        $subsector = $distributionArray['subsector'] ?? null;
        unset($distributionArray['subsector']);

        if (isset($distributionArray['assistance_type']) && AssistanceType::ACTIVITY === $distributionArray['assistance_type']) {
            unset($distributionArray['commodities']);

            // ignore user defined commodities and create some generic instead
            $modalityType = $this->em->getRepository(ModalityType::class)->findOneBy(['name' => 'Activity item']);
            $distributionArray['commodities'][] = ['value' => 1, 'unit' => 'activity', 'description' => null, 'modality_type' => ['id' => $modalityType->getId()]];
        }

        /** @var Assistance $distribution */
        $distribution = $this->serializer->deserialize(json_encode($distributionArray), Assistance::class, 'json', [
            PropertyNormalizer::DISABLE_TYPE_ENFORCEMENT => true
        ]);
        $distribution->setUpdatedOn(new DateTime());
        $errors = $this->validator->validate($distribution);
        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[] = $error->getMessage();
            }
            throw new Exception(json_encode($errorsArray), Response::HTTP_BAD_REQUEST);
        }

        $distribution->setTargetType($distributionArray['target_type']);

        $location = $this->locationService->getLocation($countryISO3, $location);
        $distribution->setLocation($location);
        $distribution->setName(AssistanceFactory::generateName($distribution));

        $project = $this->em->getRepository(Project::class)->findOneBy(['id' => $distributionArray['project']['id']]);
        if ($project instanceof Project) {
            $distribution->setProject($project);
        }

        $distribution->setSector($sector);
        $distribution->setSubSector($subsector);

        foreach ($distribution->getCommodities() as $item) {
            $distribution->removeCommodity($item);
        }
        foreach ($distributionArray['commodities'] as $item) {
            $distribution->addCommodity($this->commodityService->create($distribution, $item, false));
        }

        $listReceivers = [];
        if (AssistanceTargetType::COMMUNITY === $distribution->getTargetType()) {
            foreach ($distributionArray['communities'] as $id) {
                $community = $this->container->get('doctrine')->getRepository(Community::class)->find($id);
                $assistanceBeneficiary = (new AssistanceBeneficiary())
                    ->setAssistance($distribution)
                    ->setBeneficiary($community)
                    ->setRemoved(0);

                $this->em->persist($assistanceBeneficiary);
                $listReceivers[] = $community->getId();
            }
        } elseif (AssistanceTargetType::INSTITUTION === $distribution->getTargetType()) {
            foreach ($distributionArray['institutions'] as $id) {
                $institution = $this->container->get('doctrine')->getRepository(Institution::class)->find($id);
                $assistanceBeneficiary = (new AssistanceBeneficiary())
                    ->setAssistance($distribution)
                    ->setBeneficiary($institution)
                    ->setRemoved(0);
                $this->em->persist($assistanceBeneficiary);

                $listReceivers[] = $institution->getId();
            }
        } else {
            $criteria = [];
            foreach ($selectionCriteriaGroup as $i => $criteriaData) {
                foreach ($criteriaData as $j => $criterionArray) {
                    $criterium = new SelectionCriterionInputType();
                    $criterium->setWeight($criterionArray['weight']);
                    $criterium->setGroup($j);
                    $criterium->setTarget($criterionArray['target']);
                    $criterium->setCondition($criterionArray['condition_string']);
                    $criterium->setField($criterionArray['field_string']);
                    $criterium->setValue($criterionArray['value'] ?? $criterionArray['value_string'] );
                    $criteria[] = $criterium;
                }
            }
            $selectionGroups = $this->selectionCriteriaFactory->createGroups($criteria);
            $listReceivers = $this->container->get('distribution.criteria_assistance_service')
                ->load(
                    $selectionGroups,
                    $project,
                    $distribution->getTargetType(),
                    $sector,
                    $subsector,
                    $distributionArray['threshold'],
                    false
                );
            $this->saveReceivers($distribution, $listReceivers);
        }

        if (isset($distributionArray['allowedProductCategoryTypes'])) {
            $distribution->setAllowedProductCategoryTypes($distributionArray['allowedProductCategoryTypes']);
        }

        if (isset($distributionArray['foodLimit'])) {
            $distribution->setFoodLimit($distributionArray['foodLimit']);
        }

        $this->em->persist($distribution);
        $this->em->flush();

        return ["distribution" => $distribution, "data" => $listReceivers];
    }

    /**
     * @param Assistance $assistance
     * @param array      $listReceivers
     *
     * @throws Exception
     */
    private function saveReceivers(Assistance $assistance, array $listReceivers)
    {
        foreach ($listReceivers['finalArray'] as $receiver => $scoreProtocol) {
            /** @var Beneficiary $beneficiary */
            $beneficiary = $this->em->getReference('BeneficiaryBundle\Entity\Beneficiary', $receiver);

            $assistanceBeneficiary = (new AssistanceBeneficiary())
                ->setAssistance($assistance)
                ->setBeneficiary($beneficiary)
                ->setRemoved(0)
                ->setVulnerabilityScores($scoreProtocol);

            $this->em->persist($assistanceBeneficiary);
        }
    }

    /**
     * Edit a distribution
     *
     * @param Assistance $assistance
     * @param array $distributionArray
     * @return Assistance
     * @throws Exception
     */
    public function edit(Assistance $assistance, array $distributionArray)
    {
        $assistance->setDateDistribution(DateTime::createFromFormat('d-m-Y', $distributionArray['date_distribution']))
            ->setUpdatedOn(new DateTime());
        $distributionNameWithoutDate = explode('-', $assistance->getName())[0];
        $newDistributionName = $distributionNameWithoutDate . '-' . $distributionArray['date_distribution'];
        $assistance->setName($newDistributionName);

        $this->em->flush();
        return $assistance;
    }

    public function updateDateDistribution(Assistance $assistance, DateTimeInterface $date): void
    {
        $assistance
            ->setDateDistribution($date)
            ->setName(AssistanceFactory::generateName($assistance))
            ->setUpdatedOn(new DateTime());
    }

    public function updateDateExpiration(Assistance $assistance, ?DateTimeInterface $date): void
    {
        $assistance->setDateExpiration($date);
        $assistance->setUpdatedOn(new DateTime());
    }

    public function updateNote(Assistance $assistance, ?string $note): void
    {
        $assistance->setNote($note);
        $assistance->setUpdatedOn(new DateTime());
    }

    public function updateRound(Assistance $assistance, ?int $round): void
    {
        $assistance->setRound($round);
        $assistance->setName(AssistanceFactory::generateName($assistance));
        $assistance->setUpdatedOn(new DateTime());
    }



    /**
     * @param int $projectId
     * @param string $type
     * @return mixed
     */
    public function exportToCsv(int $projectId, string $type)
    {
        $exportableTable = $this->assistanceRepository->findBy(['project' => $projectId]);
        return $this->container->get('export_csv_service')->export($exportableTable, 'distributions', $type);
    }

    /**
     * @param int $projectId
     * @param string $type
     * @return mixed
     */
    public function exportToOfficialCsv(int $projectId, string $type)
    {
        $project = $this->em->getRepository(Project::class)->find($projectId);

        if (!$project) {
            throw new NotFoundHttpException("Project #$projectId missing");
        }

        $assistances = $this->assistanceRepository->findBy(['project' => $projectId, 'archived' => 0]);
        $exportableTable = [];

        $donors = implode(', ',
            array_map(function($donor) { return $donor->getShortname(); }, $project->getDonors()->toArray())
        );

        $bnfRepo = $this->em->getRepository(Beneficiary::class);

        foreach ($assistances as $assistance)
        {
            $idps = $bnfRepo->countByResidencyStatus($assistance, "IDP");
            $residents = $bnfRepo->countByResidencyStatus($assistance, "resident");
            $maleHHH = $bnfRepo->countHouseholdHeadsByGender($assistance, PersonGender::MALE);
            $femaleHHH = $bnfRepo->countHouseholdHeadsByGender($assistance, PersonGender::FEMALE);
            $maleChildrenUnder23month = $bnfRepo->countByAgeAndByGender($assistance, 1, 0, 2, $assistance->getDateDistribution());
            $femaleChildrenUnder23month = $bnfRepo->countByAgeAndByGender($assistance, 0, 0, 2, $assistance->getDateDistribution());
            $maleChildrenUnder5years = $bnfRepo->countByAgeAndByGender($assistance, 1, 2, 6, $assistance->getDateDistribution());
            $femaleChildrenUnder5years = $bnfRepo->countByAgeAndByGender($assistance, 0, 2, 6, $assistance->getDateDistribution());
            $maleUnder17years = $bnfRepo->countByAgeAndByGender($assistance, 1, 6, 18, $assistance->getDateDistribution());
            $femaleUnder17years = $bnfRepo->countByAgeAndByGender($assistance, 0, 6, 18, $assistance->getDateDistribution());
            $maleUnder59years = $bnfRepo->countByAgeAndByGender($assistance, 1, 18, 60, $assistance->getDateDistribution());
            $femaleUnder59years = $bnfRepo->countByAgeAndByGender($assistance, 0, 18, 60, $assistance->getDateDistribution());
            $maleOver60years = $bnfRepo->countByAgeAndByGender($assistance, 1, 60, 200, $assistance->getDateDistribution());
            $femaleOver60years = $bnfRepo->countByAgeAndByGender($assistance, 0, 60, 200, $assistance->getDateDistribution());
            $maleTotal = $maleChildrenUnder23month + $maleChildrenUnder5years + $maleUnder17years + $maleUnder59years + $maleOver60years;
            $femaleTotal = $femaleChildrenUnder23month + $femaleChildrenUnder5years + $femaleUnder17years + $femaleUnder59years + $femaleOver60years;
            $noFamilies = $assistance->getTargetType() === AssistanceTargetType::INDIVIDUAL ? ($maleTotal + $femaleTotal) : ($maleHHH + $femaleHHH);
            $familySize = $assistance->getTargetType() === AssistanceTargetType::HOUSEHOLD && $noFamilies ? ($maleTotal + $femaleTotal) / $noFamilies : null;
            $modalityType = $assistance->getCommodities()[0]->getModalityType()->getName();
            $beneficiaryServed =  $this->assistanceRepository->getNoServed($assistance->getId(), $modalityType);

            $commodityNames = implode(', ',
                    array_map(
                        function($commodity) { return  $commodity->getModalityType()->getName(); }, 
                        $assistance->getCommodities()->toArray()
                    )
                );
            $commodityUnit = implode(', ',
                array_map(
                    function($commodity) { return  $commodity->getUnit(); }, 
                    $assistance->getCommodities()->toArray()
                )
            );
            $numberOfUnits = implode(', ',
                array_map(
                    function($commodity) { return  $commodity->getValue(); }, 
                    $assistance->getCommodities()->toArray()
                )
            );
            
            $totalAmount = implode(', ',
                array_map(
                    function($commodity) use($noFamilies) { return  $commodity->getValue() * $noFamilies . ' ' . $commodity->getUnit(); }, 
                    $assistance->getCommodities()->toArray()
                )
            );

            
            
            $row = [
                $this->translator->trans("Navi/Elo number") => $assistance->getProject()->getInternalId() ?? " ",
                $this->translator->trans("DISTR. NO.") => $assistance->getId(),
                $this->translator->trans("Distributed by") => " ",
                $this->translator->trans("Round") => ($assistance->getRound() === null ? $this->translator->trans("N/A") : $assistance->getRound()),
                $this->translator->trans("Donor") => $donors,
                $this->translator->trans("Starting Date") => $assistance->getDateDistribution(),
                $this->translator->trans("Ending Date") => $assistance->getCompleted() ? $assistance->getUpdatedOn() : " - ",
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
                $this->translator->trans("Family\nSize") => $familySize
            ];
            $exportableTable[] = $row;
        }
        return $this->container->get('export_csv_service')->export($exportableTable, 'distributions', $type);
    }

    /**
     * @deprecated use Repository directly
     *
     * @param Assistance[] $distributions
     * @return Assistance[]
     */
    public function filterDistributions($distributions)
    {
        $distributionArray = $distributions->getValues();
        $filteredArray = array();
        /** @var Assistance $key */
        foreach ($distributionArray as $key) {
            if (!$key->getArchived()) {
                $filteredArray[] = $key;
            }
        }
        return $filteredArray;
    }

    /**
     * @param $distributions
     * @return string
     */
    public function filterQrVoucherDistributions($distributions)
    {
        $distributionArray = $distributions->getValues();
        $filteredArray = array();
        foreach ($distributionArray as $distribution) {
            $commodities = $distribution->getCommodities();
            $isQrVoucher = false;
            foreach ($commodities as $commodity) {
                if ($commodity->getModalityType()->getName() === "QR Code Voucher") {
                    $isQrVoucher = true;
                }
            }
            if ($isQrVoucher && !$distribution->getArchived()) {
                $filteredArray[] = $distribution;
            }
        }
        return $filteredArray;
    }

    /**
     * Export all distributions in a pdf
     * @param int $projectId
     * @return mixed
     */
    public function exportToPdf(int $projectId)
    {
        $exportableTable = $this->assistanceRepository->findBy(['project' => $projectId, 'archived' => false]);
        $project = $this->em->getRepository(Project::class)->find($projectId);

        try {
            $html =  $this->twig->render(
                '@Distribution/Pdf/distributions.html.twig',
                array_merge(
                    ['project' => $project,
                    'distributions' => $exportableTable],
                    $this->container->get('pdf_service')->getInformationStyle()
                )

            );

            $response = $this->container->get('pdf_service')->printPdf($html, 'landscape', 'bookletCodes');
            return $response;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    /**
     * @param Assistance $assistanceEntity
     */
    public function delete(Assistance $assistanceEntity)
    {
        $this->cache->delete(CacheTarget::assistanceId($assistanceEntity->getId()));
        if ($assistanceEntity->isValidated()) { //TODO also completed? to discuss
            $assistance = $this->assistanceFactory->hydrate($assistanceEntity);
            $assistance->archive();
            $this->assistanceRepository->save($assistance);
            return;
        }

        /** @var AssistanceBeneficiary $assistanceBeneficiary */
        foreach ($assistanceEntity->getDistributionBeneficiaries() as $assistanceBeneficiary) {
            /** @var ReliefPackage $reliefPackage */
            foreach ($assistanceBeneficiary->getReliefPackages() as $reliefPackage) {
                $this->em->remove($reliefPackage);
            }
        }

        foreach ($assistanceEntity->getCommodities() as $commodity) {
            $this->em->remove($commodity);
        }
        foreach ($assistanceEntity->getSelectionCriteria() as $criterion) {
            $this->em->remove($criterion);
        }
        foreach ($assistanceEntity->getDistributionBeneficiaries() as $assistanceBeneficiary) {
            /** @var AssistanceBeneficiary $assistanceBeneficiary */
            foreach ($assistanceBeneficiary->getReliefPackages() as $relief) {
                $this->em->remove($relief);
            }
            foreach ($assistanceBeneficiary->getTransactions() as $transaction) {
                $this->em->remove($transaction);
            }
            foreach ($assistanceBeneficiary->getSmartcardDeposits() as $deposit) {
                $this->em->remove($deposit);
            }
            foreach ($assistanceBeneficiary->getBooklets() as $booklet) {
                foreach ($booklet->getVouchers() as $voucher) {
                    foreach ($voucher->getVoucherPurchase() as $voucherPurchase) {
                        foreach ($voucherPurchase->getRecords() as $record) {
                            $this->em->remove($record);
                        }
                        $this->em->remove($voucherPurchase);
                    }
                    $this->em->remove($voucher);
                }
                $this->em->remove($booklet);
            }
            $this->em->remove($assistanceBeneficiary);
        }

        $this->em->remove($assistanceEntity);
        $this->em->flush();
    }

    /**
     * @deprecated old form of exports, will be removed after export system refactoring
     * @param Assistance $assistance
     * @param string $type
     * @return mixed
     */
    public function exportGeneralReliefDistributionToCsv(Assistance $assistance, string $type)
    {
        $distributionBeneficiaries = $this->em->getRepository(AssistanceBeneficiary::class)->findByAssistance($assistance);

        /** @var ReliefPackage[] $packages */
        $packages = array();
        $exportableTable = array();
        foreach ($distributionBeneficiaries as $db) {
            $relief = $this->em->getRepository(ReliefPackage::class)->findOneByAssistanceBeneficiary($db);

            if ($relief) {
                array_push($packages, $relief);
            }
        }

        foreach ($packages as $relief) {
            $beneficiary = $relief->getAssistanceBeneficiary()->getBeneficiary();
            $commodityNames = $relief->getModalityType();
            $commodityValues = $relief->getAmountToDistribute() . ' ' . $relief->getUnit();

            $commonFields = $beneficiary->getCommonExportFields();

            $exportableTable[] = array_merge($commonFields, array(
                    $this->translator->trans("Commodity") => $commodityNames,
                    $this->translator->trans("Value") => $commodityValues,
                    $this->translator->trans("Distributed At") => $relief->getLastModifiedAt(),
                    $this->translator->trans("Notes Distribution") => $relief->getNotes(),
                    $this->translator->trans("Removed") => $relief->getAssistanceBeneficiary()->getRemoved() ? 'Yes' : 'No',
                    $this->translator->trans("Justification for adding/removing") => $relief->getAssistanceBeneficiary()->getJustification(),
                )
            );
        }

        return $this->container->get('export_csv_service')->export($exportableTable, 'relief', $type);
    }

    /**
     * @deprecated old form of exports, will be removed after export system refactoring
     * @param Assistance $assistance
     * @param string $type
     * @return mixed
     */
    public function exportVouchersDistributionToCsv(Assistance $assistance, string $type)
    {
        $distributionBeneficiaries = $this->em->getRepository(AssistanceBeneficiary::class)
            ->findByAssistance($assistance);

        $beneficiaries = array();
        $exportableTable = array();
        foreach ($distributionBeneficiaries as $assistanceBeneficiary) {
            $beneficiary = $assistanceBeneficiary->getBeneficiary();
            $booklets = $assistanceBeneficiary->getBooklets();
            $transactionBooklet = null;
            if (count($booklets) > 0) {
                foreach ($booklets as $booklet) {
                    if ($booklet->getStatus() !== 3) {
                        $transactionBooklet = $booklet;
                    }
                }
                if ($transactionBooklet === null) {
                    $transactionBooklet = $booklets[0];
                }
            }

            $commonFields = $beneficiary->getCommonExportFields();

            $products = [];
            if ($transactionBooklet) {
                /** @var Voucher $voucher */
                foreach ($transactionBooklet->getVouchers() as $voucher) {
                    if ($voucher->getVoucherPurchase()) {
                        foreach ($voucher->getVoucherPurchase()->getRecords() as $record) {
                            array_push($products, $record->getProduct()->getName());
                        }
                    }
                }
            }
            $products = implode(', ', array_unique($products));

            $exportableTable[] = array_merge($commonFields, array(
                    $this->translator->trans("Booklet") => $transactionBooklet ? $transactionBooklet->getCode() : null,
                    $this->translator->trans("Status") => $transactionBooklet ? $transactionBooklet->getStatus() : null,
                    $this->translator->trans("Value") => $transactionBooklet ? $transactionBooklet->getTotalValue() . ' ' . $transactionBooklet->getCurrency() : null,
                    $this->translator->trans("Used At") => $transactionBooklet ? $transactionBooklet->getUsedAt() : null,
                    $this->translator->trans("Purchased items") => $products,
                    $this->translator->trans("Removed") => $assistanceBeneficiary->getRemoved() ? 'Yes' : 'No',
                    $this->translator->trans("Justification for adding/removing") => $assistanceBeneficiary->getJustification(),
                )
            );
        }

        return $this->container->get('export_csv_service')->export($exportableTable, 'qrVouchers', $type);
    }

    /**
     * @deprecated old form of exports, will be removed after export system refactoring
     * @param Assistance $assistance
     * @param string $type
     * @return mixed
     */
    public function exportToCsvBeneficiariesInDistribution(Assistance $assistance, string $type)
    {
        $beneficiaries = $this->em->getRepository(Beneficiary::class)->getNotRemovedofDistribution($assistance);
        return $this->container->get('export_csv_service')->export($beneficiaries, 'beneficiaryInDistribution', $type);
    }
}
