<?php declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Domain;

use BeneficiaryBundle\Entity\AbstractBeneficiary;
use CommonBundle\Entity\Location;
use DistributionBundle\Entity;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Entity\Commodity;
use DistributionBundle\Repository\ModalityTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NoResultException;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Enum\CacheTarget;
use NewApiBundle\InputType\Assistance\CommodityInputType;
use NewApiBundle\Repository\AssistanceStatisticsRepository;
use NewApiBundle\Workflow\ReliefPackageTransitions;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class Assistance
{
    /** @var Entity\Assistance */
    private $assistanceRoot;
    /** @var CacheInterface */
    private $cache;
    /** @var ModalityTypeRepository */
    private $modalityTypeRepository;
    /** @var AssistanceStatisticsRepository */
    private $assistanceStatisticRepository;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var Registry $workflowRegistry */
    private $workflowRegistry;

    /**
     * @param Entity\Assistance              $assistanceEntity
     * @param CacheInterface                 $cache
     * @param ModalityTypeRepository         $modalityTypeRepository
     * @param AssistanceStatisticsRepository $assistanceStatisticRepository
     * @param EntityManagerInterface         $entityManager
     * @param Registry                       $workflowRegistry
     */
    public function __construct(
        Entity\Assistance                    $assistanceEntity,
        CacheInterface                       $cache,
        ModalityTypeRepository               $modalityTypeRepository,
        AssistanceStatisticsRepository       $assistanceStatisticRepository,
        EntityManagerInterface               $entityManager,
        Registry $workflowRegistry
    ) {
        $this->assistanceRoot = $assistanceEntity;
        $this->cache = $cache;
        $this->modalityTypeRepository = $modalityTypeRepository;
        $this->assistanceStatisticRepository = $assistanceStatisticRepository;
        $this->entityManager = $entityManager;
        $this->workflowRegistry = $workflowRegistry;
    }

    public function getStatistics(?string $countryIso3 = null): array
    {
        $key = CacheTarget::assistanceId($this->assistanceRoot->getId());

        return $this->cache->get($key, function (ItemInterface $item) use ($countryIso3) {
            try{
                $statistics = $this->assistanceStatisticRepository->findByAssistance($this->assistanceRoot, $countryIso3);
            } catch (NoResultException $noResultException) {
                throw new NotFoundHttpException("Assistance {$this->assistanceRoot->getId()} is not in country $countryIso3");
            }

            // TODO probably better way could be normalize (or store whole) dto
            return [
                'id' => $statistics->getId(),
                'numberOfBeneficiaries' => $statistics->getNumberOfBeneficiaries(),
                'amountTotal' => $statistics->getAmountTotal(),
                'amountDistributed' => $statistics->getAmountDistributed(),
                'amountUsed' => $statistics->getAmountUsed(),
                'amountSent' => $statistics->getAmountSent(),
                'amountPickedUp' => $statistics->getAmountPickedUp(),
            ];
        });
    }

    public function validate(): self
    {
        $this->cleanCache();
        $this->assistanceRoot->setValidated(true);
        $this->assistanceRoot->setUpdatedOn(new \DateTimeImmutable());
        $this->recountReliefPackages();

        return $this;
    }

    public function unvalidate(): self
    {
        if (!$this->assistanceRoot->getValidated()) {
            throw new \InvalidArgumentException('Unable to unvalidate the assistance. Assistance wasn\'t validated.');
        }
        $this->cleanCache();

        $statistics = $this->getStatistics();

        if ($statistics['amountDistributed'] > 0) {
            throw new \InvalidArgumentException('Unable to unvalidate the assistance. Assistance is already started.');
        }
        $this->assistanceRoot->setValidated(false);
        $this->assistanceRoot->setUpdatedOn(new \DateTimeImmutable());

        return $this;
    }

    public function complete(): self
    {
        // TODO: checks
        $this->assistanceRoot->setCompleted();
        $this->expireUnusedReliefPackages();
        $this->cleanCache();

        return $this;
    }

    public function archive(): self
    {
        $this->assistanceRoot->setArchived(true);
        $this->cleanCache();
        $this->cancelUnusedReliefPackages();

        return $this;
    }

    public function addCommodity(CommodityInputType $commodityInputType): self
    {
        if ($this->assistanceRoot->getValidated()) {
            throw new \LogicException('Validated assistance shouldn\'t be edited');
        }
        $modalityType = $this->modalityTypeRepository->findOneBy(['name' => $commodityInputType->getModalityType()]);
        if (!$modalityType) {
            throw new EntityNotFoundException(sprintf('ModalityType %s does not exists', $commodityInputType->getModalityType()));
        }
        $commodity = new Entity\Commodity();
        $commodity->setModalityType($modalityType);
        $commodity->setDescription($commodityInputType->getDescription());
        $commodity->setValue($commodityInputType->getValue());
        $commodity->setUnit($commodityInputType->getUnit());
        $this->assistanceRoot->addCommodity($commodity);
        $this->recountReliefPackages();

        return $this;
    }

    public function hasDistributionStarted(): bool
    {
        $statistics = $this->getStatistics();

        return $statistics['amountDistributed'] > 0;
    }

    /**
     * @param array|null $targets who should be recounted, null => all targets in assistance
     */
    private function recountReliefPackages(?array $targets = null): void
    {
        // sum same commodities into single package
        $commodityValues = [];
        foreach ($this->assistanceRoot->getCommodities() as $commodity) {
            if (!isset($commodityValues[$commodity->getModalityType()->getName()])) {
                $commodityValues[$commodity->getModalityType()->getName()] = [];
            }
            if (!isset($commodityValues[$commodity->getModalityType()->getName()][$commodity->getUnit()])) {
                $commodityValues[$commodity->getModalityType()->getName()][$commodity->getUnit()] = 0;
            }
            $commodityValues[$commodity->getModalityType()->getName()][$commodity->getUnit()] += $commodity->getValue();
        }

        foreach ($targets ?? $this->assistanceRoot->getDistributionBeneficiaries() as $target) {
            foreach ($commodityValues as $modalityName => $values) {
                foreach ($values as $unit => $value) {
                        $target->setCommodityToDistribute($modalityName, $unit, $value);
                }
            }
        }
    }

    private function expireUnusedReliefPackages(): void
    {
        /** @var AssistanceBeneficiary $assistanceBeneficiary */
        foreach ($this->assistanceRoot->getDistributionBeneficiaries() as $assistanceBeneficiary) {
            /** @var ReliefPackage $reliefPackage */
            foreach ($assistanceBeneficiary->getReliefPackages() as $reliefPackage) {

                $reliefPackageWorkflow = $this->workflowRegistry->get($reliefPackage);

                if ($reliefPackageWorkflow->can($reliefPackage, ReliefPackageTransitions::EXPIRE)) {
                    $reliefPackageWorkflow->apply($reliefPackage, ReliefPackageTransitions::EXPIRE);
                }
            }
        }
    }

    public function getAssistanceRoot(): Entity\Assistance
    {
        return $this->assistanceRoot;
    }

    private function cancelUnusedReliefPackages(): void
    {
        /** @var AssistanceBeneficiary $assistanceBeneficiary */
        foreach ($this->assistanceRoot->getDistributionBeneficiaries() as $assistanceBeneficiary) {
            /** @var ReliefPackage $reliefPackage */
            foreach ($assistanceBeneficiary->getReliefPackages() as $reliefPackage) {

                $reliefPackageWorkflow = $this->workflowRegistry->get($reliefPackage);

                if ($reliefPackageWorkflow->can($reliefPackage, ReliefPackageTransitions::EXPIRE)) {
                    $reliefPackageWorkflow->apply($reliefPackage, ReliefPackageTransitions::EXPIRE);
                }
            }
        }
    }

    /**
     * @param AbstractBeneficiary $beneficiary
     * @param array|null          $vulnerabilityScore TODO: replace by class or serializable interface
     *
     * @return Assistance
     */
    public function addBeneficiary(AbstractBeneficiary $beneficiary, ?array $vulnerabilityScore = null): self
    {
        $target = (new AssistanceBeneficiary())
            ->setAssistance($this->assistanceRoot)
            ->setBeneficiary($beneficiary)
            ->setRemoved(false);
        if (!empty($vulnerabilityScore)) {
            $target->setVulnerabilityScores(json_encode($vulnerabilityScore));
        }
        $this->assistanceRoot->addAssistanceBeneficiary($target);
        $this->recountReliefPackages([$target]);

        return $this;
    }

    public function save(): self
    {
        $this->entityManager->persist($this->assistanceRoot);
        $this->entityManager->flush();

        return $this;
    }

    private function cleanCache(): void
    {
        try {
            $this->cache->delete(CacheTarget::assistanceId($this->assistanceRoot->getId()));
        } catch (InvalidArgumentException $e) {
            // TODO: log but ignore
        }
    }

}
