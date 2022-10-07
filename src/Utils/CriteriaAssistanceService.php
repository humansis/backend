<?php

namespace Utils;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Entity\Beneficiary;
use Exception;
use Exception\CsvParserException;
use Model\Vulnerability\Resolver as OldResolver;
use Enum\AssistanceTargetType;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Component\Assistance\DTO\CriteriaGroup;
use Component\Assistance\Scoring\Model\Factory\ScoringFactory;
use Component\Assistance\Scoring\ScoringResolver;
use Repository\ScoringBlueprintRepository;
use Entity\Project;

/**
 * Class CriteriaAssistanceService
 *
 * @package Utils
 */
class CriteriaAssistanceService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var OldResolver */
    private $oldResolver;

    /** @var ScoringFactory */
    private $scoringFactory;

    /** @var ScoringResolver */
    private $resolver;

    /** @var ScoringBlueprintRepository */
    private $scoringBlueprintRepository;

    /**
     * CriteriaAssistanceService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param OldResolver $oldResolver
     * @param ScoringResolver $resolver
     * @param ScoringBlueprintRepository $scoringBlueprintRepository
     * @throws Exception
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        OldResolver $oldResolver,
        ScoringFactory $scoringFactory,
        ScoringResolver $resolver,
        ScoringBlueprintRepository $scoringBlueprintRepository
    ) {
        $this->em = $entityManager;
        $this->oldResolver = $oldResolver;
        $this->scoringFactory = $scoringFactory;
        $this->resolver = $resolver;
        $this->scoringBlueprintRepository = $scoringBlueprintRepository;
    }

    /**
     * @param iterable|CriteriaGroup[] $criteriaGroups
     * @param Project $project
     * @param string $targetType
     * @param string $sector
     * @param string|null $subsector
     * @param int $threshold
     * @param bool $isCount
     *
     * @return array
     * @throws CsvParserException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
     * @deprecated replace by new method with type control of incoming criteria objects and country code
     */
    public function load(
        iterable $criteriaGroups,
        Project $project,
        string $targetType,
        string $sector,
        ?string $subsector,
        ?int $threshold,
        bool $isCount,
        int $scoringBlueprintId = null
    ) {
        if (
            !in_array($targetType, [
                AssistanceTargetType::INDIVIDUAL,
                AssistanceTargetType::HOUSEHOLD,
            ])
        ) {
            throw new InvalidArgumentException('Beneficiary list cannot be made by criteria for ' . $targetType);
        }

        $reachedBeneficiaries = [];
        $scoringBlueprint = $this->scoringBlueprintRepository->findActive(
            $scoringBlueprintId,
            $project->getCountryIso3()
        );
        $scoring = isset($scoringBlueprint) ? $this->scoringFactory->buildScoring($scoringBlueprint) : null;
        foreach ($criteriaGroups as $group) {
            $selectableBeneficiaries = $this->em->getRepository(Beneficiary::class)
                ->getDistributionBeneficiaries($group, $project);

            foreach ($selectableBeneficiaries as $bnf) {
                /** @var Beneficiary $beneficiary */
                $beneficiary = $this->em->getReference(Beneficiary::class, $bnf['id']);

                if (!isset($scoring)) {
                    $protocol = $this->oldResolver->compute(
                        $beneficiary->getHousehold(),
                        $project->getCountryIso3(),
                        $sector
                    );
                } else {
                    $protocol = $this->resolver->compute(
                        $beneficiary->getHousehold(),
                        $scoring,
                        $project->getCountryIso3()
                    );
                }

                if (is_null($threshold) || $protocol->getTotalScore() >= $threshold) {
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
            return ['number' => count($reachedBeneficiaries)];
        } else {
            // !!!! Those are ids, not directly beneficiaries !!!!
            return ['finalArray' => $reachedBeneficiaries];
        }
    }
}
