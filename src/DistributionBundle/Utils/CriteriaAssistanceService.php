<?php


namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Camp;
use BeneficiaryBundle\Model\Vulnerability\CategoryEnum;
use BeneficiaryBundle\Model\Vulnerability\Resolver;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Enum\AssistanceTargetType;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use NewApiBundle\Component\Assistance\DTO\CriteriaGroup;
use NewApiBundle\Entity\Assistance\SelectionCriteria;
use ProjectBundle\Entity\Project;
use Symfony\Component\Serializer\Serializer;

/**
 * Class CriteriaAssistanceService
 * @package DistributionBundle\Utils
 */
class CriteriaAssistanceService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ConfigurationLoader $configurationLoader */
    private $configurationLoader;

    /** @var Resolver */
    private $resolver;

    /** @var Serializer */
    private $serializer;

    /**
     * CriteriaAssistanceService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ConfigurationLoader    $configurationLoader
     * @param Resolver               $resolver
     * @throws \Exception
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ConfigurationLoader $configurationLoader,
        Resolver $resolver,
        Serializer $serializer
    ) {
        $this->em = $entityManager;
        $this->configurationLoader = $configurationLoader;
        $this->resolver = $resolver;
        $this->serializer = $serializer;
    }

    /**
     * @param iterable|CriteriaGroup[] $criteriaGroups
     * @param Project         $project
     * @param string          $targetType
     * @param string          $sector
     * @param string|null     $subsector
     * @param int             $threshold
     * @param bool            $isCount
     *
     * @return array
     * @throws \BeneficiaryBundle\Exception\CsvParserException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     *@deprecated replace by new method with type control of incoming criteria objects and country code
     */
    public function load(iterable $criteriaGroups, Project $project, string $targetType, string $sector, ?string $subsector, int $threshold, bool $isCount)
    {
        if (!in_array($targetType, [
            AssistanceTargetType::INDIVIDUAL,
            AssistanceTargetType::HOUSEHOLD,
        ])) {
            throw new InvalidArgumentException('Beneficiary list cannot be made by criteria for '.$targetType);
        }

        $reachedBeneficiaries = [];

        foreach ($criteriaGroups as $group)
        {
            $selectableBeneficiaries = $this->em->getRepository(Beneficiary::class)
                ->getDistributionBeneficiaries($group, $project);

            foreach ($selectableBeneficiaries as $bnf) {
                /** @var Beneficiary $beneficiary */
                $beneficiary = $this->em->getReference('BeneficiaryBundle\Entity\Beneficiary', $bnf['id']);

                $protocol = $this->resolver->compute($beneficiary->getHousehold(), $project->getIso3(), $sector);

                if ($protocol->getTotalScore() >= $threshold) {
                    if (AssistanceTargetType::INDIVIDUAL === $targetType) {
                        $BNFId = $beneficiary->getId();
                        $reachedBeneficiaries[$BNFId] = $protocol;
                    } elseif (AssistanceTargetType::HOUSEHOLD === $targetType) {
                        $HHHId = $beneficiary->getHousehold()->getHouseholdHead()->getId();
                        $reachedBeneficiaries[$HHHId] = $protocol;
                    }
                }
            }
        }
        

        if ($isCount) {
            return ['number' =>  count($reachedBeneficiaries)];
        } else {
            // !!!! Those are ids, not directly beneficiaries !!!!
            return ['finalArray' => $reachedBeneficiaries];
        }
    }

    /**
     * @param Assistance $assistance
     * @param SelectionCriteria $selectionCriteria
     * @param bool $flush
     * @return SelectionCriteria
     */
    public function save(Assistance $assistance, SelectionCriteria $selectionCriteria)
    {
        $assistance->getAssistanceSelection()->getSelectionCriteria()->add($selectionCriteria);
        $selectionCriteria->setAssistanceSelection($assistance->getAssistanceSelection());
        return $selectionCriteria;
    }

    /**
     * @param string $countryISO3
     * @return array
     */
    public function getAll(string $countryISO3)
    {
        $criteria = $this->configurationLoader->load($countryISO3);
        return $criteria;
    }

    /**
     * @param string $countryISO3
     * @return array
     */
    public function getCamps(string $countryISO3)
    {
        $camps = $this->em->getRepository(Camp::class)->findByCountry($countryISO3);
        return $camps;
    }

}
