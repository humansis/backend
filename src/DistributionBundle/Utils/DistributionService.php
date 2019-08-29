<?php

namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use CommonBundle\Utils\LocationService;
use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Entity\GeneralReliefItem;
use DistributionBundle\Entity\SelectionCriteria;
use DistributionBundle\Utils\Retriever\AbstractRetriever;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use ProjectBundle\Entity\Project;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\Product;

/**
 * Class DistributionService
 * @package DistributionBundle\Utils
 */
class DistributionService
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

    /** @var ConfigurationLoader $configurationLoader */
    private $configurationLoader;

    /** @var CriteriaDistributionService $criteriaDistributionService */
    private $criteriaDistributionService;

    /** @var AbstractRetriever $retriever */
    private $retriever;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * DistributionService constructor.
     * @param EntityManagerInterface $entityManager
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param LocationService $locationService
     * @param CommodityService $commodityService
     * @param ConfigurationLoader $configurationLoader
     * @param CriteriaDistributionService $criteriaDistributionService
     * @param string $classRetrieverString
     * @param ContainerInterface $container
     * @throws \Exception
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        Serializer $serializer,
        ValidatorInterface $validator,
        LocationService $locationService,
        CommodityService $commodityService,
        ConfigurationLoader $configurationLoader,
        CriteriaDistributionService $criteriaDistributionService,
        string $classRetrieverString,
        ContainerInterface $container
    ) {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->locationService = $locationService;
        $this->commodityService = $commodityService;
        $this->configurationLoader = $configurationLoader;
        $this->criteriaDistributionService = $criteriaDistributionService;
        $this->container = $container;
        try {
            $class = new \ReflectionClass($classRetrieverString);
            $this->retriever = $class->newInstanceArgs([$this->em]);
        } catch (\Exception $exception) {
            throw new \Exception("Your class Retriever is undefined or malformed.");
        }
    }


    /**
     * @param DistributionData $distributionData
     * @return DistributionData
     * @throws \Exception
     */
    public function validateDistribution(DistributionData $distributionData)
    {
        try {
            $distributionData->setValidated(true)
                ->setUpdatedOn(new \DateTime());
            $beneficiaries = $distributionData->getDistributionBeneficiaries();
            return $this->setCommoditiesToNewBeneficiaries($distributionData, $beneficiaries);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param DistributionData $distributionData
     * @param $beneficiaries
     * @return DistributionData
     * @throws \Exception
     */
    public function setCommoditiesToNewBeneficiaries(DistributionData $distributionData, $beneficiaries) {
        $commodities = $distributionData->getCommodities();
        foreach ($commodities as $commodity) {
            $modality = $commodity->getModalityType()->getModality();
            if ($modality->getName() === 'In Kind' ||
                $modality->getName() === 'Other' ||
                $commodity->getModalityType()->getName() === 'Paper Voucher'
                || $commodity->getModalityType()->getName() === 'Cash') {
                foreach ($beneficiaries as $beneficiary) {
                    $generalRelief = new GeneralReliefItem();
                    $generalRelief->setDistributionBeneficiary($beneficiary);
                    $this->em->persist($generalRelief);
                }
            }
        }
        $this->em->flush();

        return $distributionData;
    }

    /**
     * Create a distribution
     *
     * @param $countryISO3
     * @param array $distributionArray
     * @param int $threshold
     * @return array
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function create($countryISO3, array $distributionArray, int $threshold)
    {
        $location = $distributionArray['location'];
        unset($distributionArray['location']);
        /** @var DistributionData $distribution */
        $distribution = $this->serializer->deserialize(json_encode($distributionArray), DistributionData::class, 'json');
        $distribution->setUpdatedOn(new \DateTime());
        $errors = $this->validator->validate($distribution);
        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[] = $error->getMessage();
            }
            throw new \Exception(json_encode($errorsArray), Response::HTTP_BAD_REQUEST);
        }

        // TODO : make the front send 0 or 1 instead of Individual (Beneficiary comes from the import)
        if ($distributionArray['type'] === "Beneficiary" || $distributionArray['type'] === "Individual" || $distributionArray['type'] === "1") {
            $distribution->settype(1);
        } else {
            $distribution->settype(0);
        }

        $location = $this->locationService->getLocation($countryISO3, $location);
        $distribution->setLocation($location);

        $project = $distribution->getProject();
        $projectTmp = $this->em->getRepository(Project::class)->find($project);
        if ($projectTmp instanceof Project) {
            $distribution->setProject($projectTmp);
        }


        foreach ($distribution->getCommodities() as $item) {
            $distribution->removeCommodity($item);
        }
        foreach ($distributionArray['commodities'] as $item) {
            $this->commodityService->create($distribution, $item, false);
        }
        $criteria = [];
        foreach ($distribution->getSelectionCriteria() as $item) {
            $distribution->removeSelectionCriterion($item);
            if ($item->getTableString() == null) {
                $item->setTableString("Beneficiary");
            }

            $criteria[] = $this->criteriaDistributionService->save($distribution, $item, false);
        }

        $this->em->persist($distribution);
        $this->em->flush();

        $this->em->persist($distribution);

        $listReceivers = $this->guessBeneficiaries($distributionArray, $countryISO3, $distributionArray['type'], $projectTmp, $threshold);
        $this->saveReceivers($distribution, $listReceivers);

        $this->em->flush();

        return ["distribution" => $distribution, "data" => $listReceivers];
    }

    /**
     * @param array $criteria
     * @param $countryISO3
     * @param $type
     * @param Project $project
     * @param int $threshold
     * @return mixed
     */
    public function guessBeneficiaries(array $criteria, $countryISO3, $type, Project $project, int $threshold)
    {
        $criteria['criteria'] = $criteria['selection_criteria'];
        $criteria['countryIso3'] = $countryISO3;
        $criteria['distribution_type'] = $type;

        return $this->container->get('distribution.criteria_distribution_service')->load($criteria, $project, $threshold, false);
    }

    /**
     * @param DistributionData $distributionData
     * @param array $listReceivers
     * @throws \Exception
     */
    public function saveReceivers(DistributionData $distributionData, array $listReceivers)
    {
        foreach ($listReceivers['finalArray'] as $receiver) {
        $distributionBeneficiary = new DistributionBeneficiary();
        $distributionBeneficiary->setDistributionData($distributionData)
            ->setBeneficiary($this->em->getReference('BeneficiaryBundle\Entity\Beneficiary', $receiver))
            ->setRemoved(0);
           
            $this->em->persist($distributionBeneficiary);
        }
    }

    /**
     * Get all distributions
     *
     * @return array
     */
    public function findAll(string $country)
    {
        $distributions = [];
        $projects = $this->em->getRepository(Project::class)->findAll();
        
        foreach ($projects as $proj) {
            if ($proj->getIso3() == $country) {
                foreach ($proj->getDistributions() as $distrib) {
                    array_push($distributions, $distrib);
                }
            }
        }

        return $distributions;
    }


    /**
     * Get all distributions
     *
     * @param int $id
     * @return null|object
     */
    public function findOneById(int $id)
    {
        return $this->em->getRepository(DistributionData::class)->findOneBy(['id' => $id]);
    }

    /**
     * @param DistributionData $distributionData
     * @return null|object|string
     */
    public function archived(DistributionData $distributionData)
    {
        if (!empty($distributionData)) {
            $distributionData->setArchived(1);
        }

        $this->em->persist($distributionData);
        $this->em->flush();

        return "Archived";
    }

    /**
     * @param DistributionData $distributionData
     * @return null|object|string
     */
    public function complete(DistributionData $distributionData)
    {
        if (!empty($distributionData)) {
            $distributionBeneficiaries = $distributionData->getDistributionBeneficiaries();
            $completed = 1;
            foreach ($distributionBeneficiaries as $distributionBeneficiary) {
                $generalReliefs = $distributionBeneficiary->getGeneralReliefs();
                foreach ($generalReliefs as $generalRelief) {
                    Dump($generalRelief->getDistributedAt());
                    if ($generalRelief->getDistributedAt() === null) {
                        $completed = 0;
                    }
                }
            }
            Dump($completed);
            if ($completed === 1) {
                $distributionData->setCompleted(1)
                                ->setUpdatedOn(new \DateTime);         
            }
        }

        $this->em->persist($distributionData);
        $this->em->flush();

        if ($completed === 1) {
            return "Completed";
        } else {
            return "Not completed";
        }
    }

    /**
     * Edit a distribution
     *
     * @param DistributionData $distributionData
     * @param array $distributionArray
     * @return DistributionData
     * @throws \Exception
     */
    public function edit(DistributionData $distributionData, array $distributionArray)
    {
        $distributionData->setDateDistribution(\DateTime::createFromFormat('d-m-Y', $distributionArray['date_distribution']))
            ->setUpdatedOn(new \DateTime());
        $distributionNameWithoutDate = explode('-', $distributionData->getName())[0];
        $newDistributionName = $distributionNameWithoutDate . '-' . $distributionArray['date_distribution'];
        $distributionData->setName($newDistributionName);

        $this->em->flush();
        return $distributionData;
    }


    /**
     * @param int $projectId
     * @param string $type
     * @return mixed
     */
    public function exportToCsv(int $projectId, string $type)
    {
        $exportableTable = $this->em->getRepository(DistributionData::class)->findBy(['project' => $projectId]);
        return $this->container->get('export_csv_service')->export($exportableTable, 'distributions', $type);
    }

    /**
     * @param int $projectId
     * @param string $type
     * @return mixed
     */
    public function exportToOfficialCsv(int $projectId, string $type)
    {
        $distributions = $this->em->getRepository(DistributionData::class)->findBy(['project' => $projectId]);
        $project = $this->em->getRepository(Project::class)->find($projectId);
        $exportableTable = [];

        $donors = implode(', ',
            array_map(function($donor) { return $donor->getShortname(); }, $project->getDonors()->toArray())
        );
        
        foreach ($distributions as $distribution) {

            $idps = $this->em->getRepository(DistributionData::class)->getNoBenificiaryByResidencyStatus($distribution->getId(), "IDP", $distribution->getType());
            $residents = $this->em->getRepository(DistributionData::class)->getNoBenificiaryByResidencyStatus($distribution->getId(), "resident", $distribution->getType());
            $maleHHH = $this->em->getRepository(DistributionData::class)->getNoHeadHouseholdsByGender($distribution->getId(), 1);
            $femaleHHH = $this->em->getRepository(DistributionData::class)->getNoHeadHouseholdsByGender($distribution->getId(), 0);
            $maleChildrenUnder23month = $this->em->getRepository(DistributionData::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 1, 0, 2, $distribution->getDateDistribution(), $distribution->getType());
            $femaleChildrenUnder23month = $this->em->getRepository(DistributionData::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 0, 0, 2, $distribution->getDateDistribution(), $distribution->getType());
            $maleChildrenUnder5years = $this->em->getRepository(DistributionData::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 1, 2, 6, $distribution->getDateDistribution(), $distribution->getType());
            $femaleChildrenUnder5years = $this->em->getRepository(DistributionData::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 0, 2, 6, $distribution->getDateDistribution(), $distribution->getType());
            $maleUnder17years = $this->em->getRepository(DistributionData::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 1, 6, 18, $distribution->getDateDistribution(), $distribution->getType());
            $femaleUnder17years = $this->em->getRepository(DistributionData::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 0, 6, 18, $distribution->getDateDistribution(), $distribution->getType());
            $maleUnder59years = $this->em->getRepository(DistributionData::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 1, 18, 60, $distribution->getDateDistribution(), $distribution->getType());
            $femaleUnder59years = $this->em->getRepository(DistributionData::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 0, 18, 60, $distribution->getDateDistribution(), $distribution->getType());
            $maleOver60years = $this->em->getRepository(DistributionData::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 1, 60, 200, $distribution->getDateDistribution(), $distribution->getType());
            $femaleOver60years = $this->em->getRepository(DistributionData::class)->getNoBenificiaryByAgeAndByGender($distribution->getId(), 0, 60, 200, $distribution->getDateDistribution(), $distribution->getType());
            $maleTotal = $maleChildrenUnder23month + $maleChildrenUnder5years + $maleUnder17years + $maleUnder59years + $maleOver60years;
            $femaleTotal = $femaleChildrenUnder23month + $femaleChildrenUnder5years + $femaleUnder17years + $femaleUnder59years + $femaleOver60years;
            $noFamilies = $distribution->getType() === DistributionData::TYPE_BENEFICIARY ? ($maleTotal + $femaleTotal) : ($maleHHH + $femaleHHH);
            $familySize = $distribution->getType() === DistributionData::TYPE_HOUSEHOLD && $noFamilies ? ($maleTotal + $femaleTotal) / $noFamilies : null;
            $modalityType = $distribution->getCommodities()[0]->getModalityType()->getName();
            $beneficiaryServed =  $this->em->getRepository(DistributionData::class)->getNoServed($distribution->getId(), $modalityType);

            $commodityNames = implode(', ',
                    array_map(
                        function($commodity) { return  $commodity->getModalityType()->getName(); }, 
                        $distribution->getCommodities()->toArray()
                    )
                );
            $commodityUnit = implode(', ',
                array_map(
                    function($commodity) { return  $commodity->getUnit(); }, 
                    $distribution->getCommodities()->toArray()
                )
            );
            $numberOfUnits = implode(', ',
                array_map(
                    function($commodity) { return  $commodity->getValue(); }, 
                    $distribution->getCommodities()->toArray()
                )
            );
            
            $totalAmount = implode(', ',
                array_map(
                    function($commodity) use($noFamilies) { return  $commodity->getValue() * $noFamilies . ' ' . $commodity->getUnit(); }, 
                    $distribution->getCommodities()->toArray()
                )
            );

            
            
            $row = [
                "Navi/Elo number" => " ",
                "DISTR. NO." => $distribution->getId(),
                "Distributed by" => " ",
                "Round" => " ",
                "Donor" => $donors,
                "Starting Date" => $distribution->getDateDistribution(),
                "Ending Date" => $distribution->getCompleted() ? $distribution->getUpdatedOn() : " - ",
                "Governorate" => $distribution->getLocation()->getAdm1Name(),
                "District" => $distribution->getLocation()->getAdm2Name(),
                "Sub-District" => $distribution->getLocation()->getAdm3Name(),
                "Town, Village" => $distribution->getLocation()->getAdm4Name(),
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
     * @param string $country
     * @return int
     */
    public function countAllBeneficiaries(string $country)
    {
        $count = (int) $this->em->getRepository(DistributionBeneficiary::class)->countAll($country);
        return $count;
    }
    
    /**
     * @param string $country
     * @return string
     */
    public function getTotalValue(string $country)
    {
        $value = (int) $this->em->getRepository(DistributionData::class)->getTotalValue($country);
        return $value;
    }

     /**
     * @param string $country
     * @return string
     */
    public function countCompleted(string $country)
    {
        $value = (int) $this->em->getRepository(DistributionData::class)->countCompleted($country);
        return $value;
    }

    /**
     * @param $distributions
     * @return string
     */
    public function filterDistributions($distributions)
    {
        $distributionArray = $distributions->getValues();
        $filteredArray = array();
        foreach ($distributionArray as $key) {
            if (!$key->getArchived()) {
                $filteredArray[] = $key;
            };
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
            };
        }
        return $filteredArray;
    }

    /**
     * @param $country
     * @return string
     */
    public function getActiveDistributions($country)
    {
        $active = $this->em->getRepository(DistributionData::class)->getActiveByCountry($country);
        return $active;
    }
    
    /**
     * Initialise GRI for a distribution
     * @param  DistributionData $distributionData
     * @return void
     */
    public function createGeneralReliefItems(DistributionData $distributionData)
    {
        $distributionBeneficiaries = $distributionData->getDistributionBeneficiaries();
        foreach ($distributionBeneficiaries as $index => $distributionBeneficiary) {
            $$index = new GeneralReliefItem();
            $$index->setDistributionBeneficiary($distributionBeneficiary);
            $distributionBeneficiary->addGeneralRelief($$index);

            $this->em->persist($$index);
            $this->em->merge($distributionBeneficiary);
        }
        $this->em->flush();
    }

    /**
     * Edit notes of general relief item
     * @param  GeneralReliefItem $generalRelief
     * @param  string            $notes
     */
    public function editGeneralReliefItemNotes(int $id, string $notes)
    {
        try {
            $generalRelief = $this->em->getRepository(GeneralReliefItem::class)->find($id);
            $generalRelief->setNotes($notes);
            $this->em->flush();
        } catch (\Exception $e) {
            throw new \Exception("Error updating general relief item");
        }
    }

    /**
     * Set general relief items as distributed
     * @param array    $griIds
     * @param DateTime $distributedAt
     * @return array
     */
    public function setGeneralReliefItemsAsDistributed(array $griIds)
    {
        $errorArray = array();
        $successArray = array();

        foreach ($griIds as $griId) {
            $gri = $this->em->getRepository(GeneralReliefItem::class)->find($griId);

            if (!($gri instanceof GeneralReliefItem)) {
                array_push($errorArray, $griId);
            } else {
                $gri->setDistributedAt(new \DateTime());
                $this->em->merge($gri);
                array_push($successArray, $gri);
            }
        }

        $this->em->flush();

        return array($errorArray, $successArray);
    }
    
    /**
     * @param DistributionData $distributionData
     * @param string $type
     * @return mixed
     */
    public function exportGeneralReliefDistributionToCsv(DistributionData $distributionData, string $type)
    {
        $distributionBeneficiaries = $this->em->getRepository(DistributionBeneficiary::class)->findByDistributionData($distributionData);

        $generalreliefs = array();
        $exportableTable = array();
        foreach ($distributionBeneficiaries as $db) {
            $generalrelief = $this->em->getRepository(GeneralReliefItem::class)->findOneByDistributionBeneficiary($db);

            if ($generalrelief) {
                array_push($generalreliefs, $generalrelief);
            }
        }

        foreach ($generalreliefs as $generalrelief) {
            $beneficiary = $generalrelief->getDistributionBeneficiary()->getBeneficiary();
            $commodityNames = implode(', ',
                array_map(
                    function($commodity) { return  $commodity->getModalityType()->getName(); }, 
                    $distributionData->getCommodities()->toArray()
                )
            );


            $commodityValues = implode(', ',
                array_map(
                    function($commodity) { return  $commodity->getValue() . ' ' . $commodity->getUnit(); }, 
                    $distributionData->getCommodities()->toArray()
                )
            );

            $commonFields = $beneficiary->getCommonExportFields();

            array_push($exportableTable, 
                array_merge($commonFields, array(
                    "Commodity" => $commodityNames,
                    "Value" => $commodityValues,
                    "Distributed At" => $generalrelief->getDistributedAt(),
                    "Notes Distribution" => $generalrelief->getNotes(),
                    "Removed" => $generalrelief->getDistributionBeneficiary()->getRemoved() ? 'Yes' : 'No',
                    "Justification for adding/removing" => $generalrelief->getDistributionBeneficiary()->getJustification(),
                ))
            );
        }

        return $this->container->get('export_csv_service')->export($exportableTable, 'generalrelief', $type);
    }


    /**
     * Export all distributions in a pdf
     * @param int $projectId
     * @return mixed
     */
    public function exportToPdf(int $projectId)
    {
        $exportableTable = $this->em->getRepository(DistributionData::class)->findBy(['project' => $projectId, 'archived' => false]);
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
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

     /**
     * Export a distribution in pdf
     * @param int $distributionId
     * @return mixed
     */
    public function exportOneToPdf(int $distributionId)
    {
        $exportableDistribution = $this->em->getRepository(DistributionData::class)->findOneBy(['id' => $distributionId, 'archived' => false]);

        $booklets = [];

        if ($exportableDistribution->getCommodities()[0]->getModalityType()->getName() === 'QR Code Voucher') {
            foreach ($exportableDistribution->getDistributionBeneficiaries() as $distributionBeneficiary) {
                    $activatedBooklets = $this->em->getRepository(Booklet::class)->getActiveBookletsByDistributionBeneficiary($distributionBeneficiary->getId());
                    if (count($activatedBooklets) > 0) {
                        $products = $this->em->getRepository(Product::class)->getNameByBooklet($activatedBooklets[0]->getId());
                        $products = array_map(
                            function($product) { return $product['name']; },
                            $products
                        );
                        $booklet = [
                            "code" => $activatedBooklets[0]->getCode(),
                            "status" => $activatedBooklets[0]->getStatus(),
                            "vouchers" => $activatedBooklets[0]->getVouchers(),
                            "products" => implode(', ', $products),
                            "value" => $activatedBooklets[0]->getTotalValue(),
                            "currency" => $activatedBooklets[0]->getCurrency(),
                            "usedAt" => $activatedBooklets[0]->getUsedAt()
                        ];
                        $booklets[$distributionBeneficiary->getId()] = $booklet;
                    }
            }
        }

        try {
            $html =  $this->container->get('templating')->render(
                '@Distribution/Pdf/distribution.html.twig',
                array_merge(
                    [
                        'distribution' => $exportableDistribution,
                        'booklets' => $booklets,
                        'commodities' => implode(', ', array_map(
                            function($commodity) { return $commodity->getValue() . ' ' . $commodity->getUnit() . '/pers'; }, 
                            $exportableDistribution->getCommodities()->toArray()
                        ))
                    ],
                    $this->container->get('pdf_service')->getInformationStyle()
                )
            );

            $response = $this->container->get('pdf_service')->printPdf($html, 'portrait', 'bookletCodes');
            return $response;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
