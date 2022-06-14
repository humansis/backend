<?php

namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\AbstractBeneficiary;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Exception\CsvParserException;
use CommonBundle\Entity\Location;
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
use NewApiBundle\Component\Assistance\SelectionCriteriaFactory;
use NewApiBundle\Component\SelectionCriteria\FieldDbTransformer;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Entity\Assistance\SelectionCriteria;
use NewApiBundle\Enum\CacheTarget;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\InputType\AssistanceCreateInputType;
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

    /** @var FieldDbTransformer */
    private $fieldDbTransformer;

    /** @var CacheInterface */
    private $cache;

    /** @var AssistanceFactory */
    private $assistanceFactory;

    /** @var AssistanceRepository */
    private $assistanceRepository;

    /** @var SelectionCriteriaFactory */
    private $selectionCriteriaFactory;

    /**
     * AssistanceService constructor.
     *
     * @param EntityManagerInterface    $entityManager
     * @param Serializer                $serializer
     * @param ValidatorInterface        $validator
     * @param LocationService           $locationService
     * @param CommodityService          $commodityService
     * @param CriteriaAssistanceService $criteriaAssistanceService
     * @param FieldDbTransformer        $fieldDbTransformer
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
        FieldDbTransformer        $fieldDbTransformer,
        ContainerInterface        $container,
        CacheInterface            $cache,
        AssistanceFactory         $assistanceFactory,
        AssistanceRepository      $assistanceRepository,
        SelectionCriteriaFactory  $selectionCriteriaFactory
    ) {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->locationService = $locationService;
        $this->commodityService = $commodityService;
        $this->criteriaAssistanceService = $criteriaAssistanceService;
        $this->fieldDbTransformer = $fieldDbTransformer;
        $this->container = $container;
        $this->cache = $cache;
        $this->assistanceFactory = $assistanceFactory;
        $this->assistanceRepository = $assistanceRepository;
        $this->selectionCriteriaFactory = $selectionCriteriaFactory;
    }

    /**
     * @param Assistance $assistanceRoot
     *
     * @deprecated use Assistance::validate instead
     */
    public function validateDistribution(Assistance $assistanceRoot)
    {
        $assistance = $this->assistanceFactory->hydrate($assistanceRoot);
        $assistance->validate();
        $this->assistanceRepository->save($assistance);
    }

    // TODO: presunout do ABNF
    public function findByCriteria(AssistanceCreateInputType $inputType, Pagination $pagination)
    {
        $project = $this->em->getRepository(Project::class)->find($inputType->getProjectId());
        if (!$project) {
            throw new EntityNotFoundException('Project #'.$inputType->getProjectId().' does not exists.');
        }

        $filters = $this->mapping($inputType);
        $filters['criteria'] = $filters['selection_criteria'];

        $result = $this->criteriaAssistanceService->load($filters, $project, $inputType->getTarget(), $inputType->getSector(), $inputType->getSubsector(), $inputType->getThreshold(), false);
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

        $filters = $this->mapping($inputType);
        $filters['criteria'] = $filters['selection_criteria'];

        $result = $this->criteriaAssistanceService->load($filters, $project, $inputType->getTarget(), $inputType->getSector(), $inputType->getSubsector(), $inputType->getThreshold(), false);
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
        $distribution->setName(self::generateName($location, $distribution->getDateDistribution()));

        $project = $distribution->getProject();
        $projectTmp = $this->em->getRepository(Project::class)->find($project);
        if ($projectTmp instanceof Project) {
            $distribution->setProject($projectTmp);
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
                    /** @var SelectionCriteria $criterion */
                    $criterion = $this->serializer->deserialize(json_encode($criterionArray), SelectionCriteria::class, 'json', [
                        PropertyNormalizer::DISABLE_TYPE_ENFORCEMENT => true
                    ]);
                    $criterion->setGroupNumber($i);
                    $this->criteriaAssistanceService->save($distribution, $criterion);
                    $this->em->persist($criterion);
                    $criteria[$i][$j] = $this->selectionCriteriaFactory->hydrate($criterion);
                }
            }

            $distributionArray['selection_criteria'] = $criteria;
            $listReceivers = $this->guessBeneficiaries($distributionArray, $countryISO3, $projectTmp, $distribution->getTargetType(), $sector, $subsector, $distributionArray['threshold']);
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
     * @deprecated dont use at all
     * @param array   $criteria
     * @param         $countryISO3
     * @param Project $project
     * @param string  $targetType
     * @param         $sector
     * @param         $subsector
     * @param int     $threshold
     *
     * @return mixed
     */
    private function guessBeneficiaries(array $criteria, $countryISO3, Project $project, string $targetType, $sector, $subsector, int $threshold)
    {
        $criteria['criteria'] = $criteria['selection_criteria'];
        $criteria['countryIso3'] = $countryISO3;

        return $this->container->get('distribution.criteria_assistance_service')->load($criteria, $project, $targetType, $sector, $subsector, $threshold, false);
    }

    /**
     * @param Assistance $assistance
     * @param array      $listReceivers
     *
     * @throws Exception
     */
    private function saveReceivers(Assistance $assistance, array $listReceivers)
    {
        foreach ($listReceivers['finalArray'] as $receiver => $scores) {
            /** @var Beneficiary $beneficiary */
            $beneficiary = $this->em->getReference('BeneficiaryBundle\Entity\Beneficiary', $receiver);

            $assistanceBeneficiary = (new AssistanceBeneficiary())
                ->setAssistance($assistance)
                ->setBeneficiary($beneficiary)
                ->setRemoved(0)
                ->setVulnerabilityScores(json_encode($scores));

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

    public function updateDateDistribution(Assistance $assistance, DateTimeInterface $date)
    {
        $newDistributionName = self::generateName($assistance->getLocation(), $date);

        $assistance
            ->setDateDistribution($date)
            ->setName($newDistributionName)
            ->setUpdatedOn(new DateTime());

        $this->em->persist($assistance);
        $this->em->flush();
    }

    public function updateDateExpiration(Assistance $assistance, DateTimeInterface $date): void
    {
        $assistance->setDateExpiration($date);
        $assistance->setUpdatedOn(new DateTime());

        $this->em->persist($assistance);
        $this->em->flush();
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
                "Navi/Elo number" => $assistance->getProject()->getInternalId() ?? " ",
                "DISTR. NO." => $assistance->getId(),
                "Distributed by" => " ",
                "Round" => " ",
                "Donor" => $donors,
                "Starting Date" => $assistance->getDateDistribution(),
                "Ending Date" => $assistance->getCompleted() ? $assistance->getUpdatedOn() : " - ",
                "Governorate" => $assistance->getLocation()->getAdm1Name(),
                "District" => $assistance->getLocation()->getAdm2Name(),
                "Sub-District" => $assistance->getLocation()->getAdm3Name(),
                "Town, Village" => $assistance->getLocation()->getAdm4Name(),
                "Location = School/Camp" => " ",
                "Neighbourhood (Camp Name)" => " ",
                "Latitude" => " ",
                "Longitude" => " ",
                // "Location Code" => $distribution->getLocation()->getCode(),
                "Activity (Modality)" => $commodityNames,
                "UNIT" => $commodityUnit,
                "Nº Of Units" => $numberOfUnits,
                "Amount (USD/SYP)" => " ",
                "Total Amount" => $totalAmount,
                "Bebelac Type" => " ",
                "Water\nNº of 1.5 bottles " => " ",
                "Bebelac kg" => " ",
                "Nappies Pack" => " ",
                "IDPs" => $idps,
                "Residents" => $residents,
                "Nº FAMILIES" => $noFamilies,
                "FEMALE\nHead of Family gender" => $femaleHHH,
                "MALE\nHead of Family gender" => $maleHHH,
                /*
                * Male and Female children from 0 to 17 months
                */
                "Children\n0-23 months\nMale" => $maleChildrenUnder23month,
                "Children\n0-23 months\nFemale" => $femaleChildrenUnder23month,
                //"Children\n2-5" => $childrenUnder5years
                "Children\n2-5\nMale" => $maleChildrenUnder5years,
                "Children\n2-5\nFemale" => $femaleChildrenUnder5years,
                "Males\n6-17" => $maleUnder17years,
                "Females\n6-17" => $femaleUnder17years,
                "Males\n18-59" => $maleUnder59years,
                "Females\n18-59" => $femaleUnder59years,
                "Males\n60+" => $maleOver60years,
                "Females\n60+" => $femaleOver60years,
                "Total\nMales" => $maleTotal,
                "Total\nFemales" => $femaleTotal,
                "Individ. Benef.\nServed" => $beneficiaryServed,
                "Family\nSize" => $familySize
            ];
            array_push($exportableTable, $row);
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
            $html =  $this->container->get('templating')->render(
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
        if ($assistanceEntity->getValidated()) { //TODO also completed? to discuss
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

    private function generateName(Location $location, ?DateTimeInterface $date = null): string
    {
        $adm = '';
        if ($location->getAdm4()) {
            $adm = $location->getAdm4()->getName();
        } elseif ($location->getAdm3()) {
            $adm = $location->getAdm3()->getName();
        } elseif ($location->getAdm2()) {
            $adm = $location->getAdm2()->getName();
        } elseif ($location->getAdm1()) {
            $adm = $location->getAdm1()->getName();
        }

        if ($date) {
            return $adm.'-'.$date->format('d-m-Y');
        } else {
            return $adm.'-'.date('d-m-Y');
        }
    }

    private function mapping(AssistanceCreateInputType $inputType): array
    {
        /** @var Location $location */
        $location = $this->em->getRepository(Location::class)->find($inputType->getLocationId());

        $locationArray = [];
        if ($location->getAdm4()) {
            $locationArray = [
                'adm1' => $location->getAdm4()->getAdm3()->getAdm2()->getAdm1()->getId(),
                'adm2' => $location->getAdm4()->getAdm3()->getAdm2()->getId(),
                'adm3' => $location->getAdm4()->getAdm3()->getId(),
                'adm4' => $location->getAdm4()->getId(),
                'country_iso3' => $inputType->getIso3(),
            ];
        } elseif ($location->getAdm3()){
            $locationArray = [
                'adm1' => $location->getAdm3()->getAdm2()->getAdm1()->getId(),
                'adm2' => $location->getAdm3()->getAdm2()->getId(),
                'adm3' => $location->getAdm3()->getId(),
                'adm4' => null,
                'country_iso3' => $inputType->getIso3(),
            ];
        } elseif ($location->getAdm2()){
            $locationArray = [
                'adm1' => $location->getAdm2()->getAdm1()->getId(),
                'adm2' => $location->getAdm2()->getId(),
                'adm3' => null,
                'adm4' => null,
                'country_iso3' => $inputType->getIso3(),
            ];
        } elseif ($location->getAdm1()){
            $locationArray = [
                'adm1' => $location->getAdm1()->getId(),
                'adm2' => null,
                'adm3' => null,
                'adm4' => null,
                'country_iso3' => $inputType->getIso3(),
            ];
        }

        $distributionArray = [
            'countryIso3' => $inputType->getIso3(),
            'assistance_type' => $inputType->getType(),
            'target_type' => $inputType->getTarget(),
            'date_distribution' => $inputType->getDateDistribution(),
            'date_expiration' => $inputType->getDateExpiration(),
            'project' => ['id' => $inputType->getProjectId()],
            'location' => $locationArray,
            'sector' => $inputType->getSector(),
            'subsector' => $inputType->getSubsector(),
            'threshold' => $inputType->getThreshold(),
            'institutions' => $inputType->getInstitutions(),
            'communities' => $inputType->getCommunities(),
            'households_targeted' => $inputType->getHouseholdsTargeted(),
            'individuals_targeted' => $inputType->getIndividualsTargeted(),
            'description' => $inputType->getDescription(),
            'foodLimit' => $inputType->getFoodLimit(),
            'nonfoodLimit' => $inputType->getNonFoodLimit(),
            'cashbackLimit' => $inputType->getCashbackLimit(),
            'remoteDistributionAllowed' => $inputType->getRemoteDistributionAllowed(),
            'allowedProductCategoryTypes' => $inputType->getAllowedProductCategoryTypes(),
        ];

        foreach ($inputType->getCommodities() as $commodity) {
            $modalityType = $this->em->getRepository(ModalityType::class)->findOneBy(['name' => $commodity->getModalityType()]);
            if (!$modalityType) {
                throw new EntityNotFoundException(sprintf('ModalityType %s does not exists', $commodity->getModalityType()));
            }
            $distributionArray['commodities'][] = [
                'value' => $commodity->getValue(),
                'unit' => $commodity->getUnit(),
                'description' => $commodity->getDescription(),
                'modality_type' => ['id' => $modalityType->getId()],
            ];
        }

        foreach ($inputType->getSelectionCriteria() as $criterion) {
            $distributionArray['selection_criteria'][$criterion->getGroup()][] = $this->selectionCriteriaFactory->hydrate(
                $this->fieldDbTransformer->toDbArray($criterion)
            );
        }

        return $distributionArray;
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

            array_push($exportableTable,
                array_merge($commonFields, array(
                    "Commodity" => $commodityNames,
                    "Value" => $commodityValues,
                    "Distributed At" => $relief->getLastModifiedAt(),
                    "Notes Distribution" => $relief->getNotes(),
                    "Removed" => $relief->getAssistanceBeneficiary()->getRemoved() ? 'Yes' : 'No',
                    "Justification for adding/removing" => $relief->getAssistanceBeneficiary()->getJustification(),
                ))
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

            array_push(
                $exportableTable,
                array_merge($commonFields, array(
                    "Booklet" => $transactionBooklet ? $transactionBooklet->getCode() : null,
                    "Status" => $transactionBooklet ? $transactionBooklet->getStatus() : null,
                    "Value" => $transactionBooklet ? $transactionBooklet->getTotalValue() . ' ' . $transactionBooklet->getCurrency() : null,
                    "Used At" => $transactionBooklet ? $transactionBooklet->getUsedAt() : null,
                    "Purchased items" => $products,
                    "Removed" => $assistanceBeneficiary->getRemoved() ? 'Yes' : 'No',
                    "Justification for adding/removing" => $assistanceBeneficiary->getJustification(),
                ))
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
