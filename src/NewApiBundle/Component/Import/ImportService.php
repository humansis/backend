<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use BeneficiaryBundle\Entity\Household;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use NewApiBundle\Component\Import\ValueObject\ImportStatisticsValueObject;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportBeneficiary;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Entity\ImportFile;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportDuplicityState;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\InputType\DuplicityResolveInputType;
use NewApiBundle\InputType\ImportCreateInputType;
use NewApiBundle\InputType\ImportUpdateStatusInputType;
use NewApiBundle\Repository\ImportQueueRepository;
use ProjectBundle\Entity\Project;
use UserBundle\Entity\User;

class ImportService
{
    /** @var EntityManagerInterface $em */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function create(ImportCreateInputType $inputType, User $user): Import
    {
        $project = $this->em->getRepository(Project::class)->find($inputType->getProjectId());

        if (!$project instanceof Project) {
            throw new InvalidArgumentException('Project with ID '.$inputType->getProjectId().' not found');
        }

        $import = new Import(
            $inputType->getTitle(),
            $inputType->getDescription(),
            $project,
            $user,
        );

        $this->em->persist($import);
        $this->em->flush();

        return $import;
    }

    public function updateStatus(Import $import, ImportUpdateStatusInputType $inputType): void
    {
        $import->setState($inputType->getStatus());

        $this->em->flush();
    }

    public function removeFile(ImportFile $importFile)
    {
        $this->em->remove($importFile);

        $this->em->flush();
    }

    public function getStatistics(Import $import): ImportStatisticsValueObject
    {
        $statistics = new ImportStatisticsValueObject();

        /** @var ImportQueueRepository $repository */
        $repository = $this->em->getRepository(ImportQueue::class);

        $statistics->setTotalEntries($import->getImportQueue()->count());
        $statistics->setAmountIntegrityCorrect($repository->getTotalByImportAndStatus($import, ImportQueueState::VALID));
        $statistics->setAmountIntegrityFailed($repository->getTotalByImportAndStatus($import, ImportQueueState::INVALID));
        $statistics->setAmountDuplicities($repository->getTotalByImportAndStatus($import, ImportQueueState::SUSPICIOUS));
        $statistics->setAmountDuplicitiesResolved($repository->getTotalReadyForSave($import));
        $statistics->setAmountEntriesToImport($repository->getTotalReadyForSave($import));

        return $statistics;
    }

    public function resolveDuplicity(ImportQueue $importQueue, DuplicityResolveInputType $inputType, User $user)
    {
        $importQueue->setState($inputType->getStatus());

        /** @var ImportBeneficiaryDuplicity[] $duplicities */
        $duplicities = $this->em->getRepository(ImportBeneficiaryDuplicity::class)->findBy([
            'ours' => $importQueue,
        ]);

        foreach ($duplicities as $duplicity) {
            if ($duplicity->getId() === $inputType->getAcceptedDuplicityId()) {

                switch ($inputType->getStatus()) {
                    case ImportQueueState::TO_UPDATE:
                        $duplicity->setState(ImportDuplicityState::DUPLICITY_KEEP_OURS);
                        break;
                    case ImportQueueState::TO_LINK:
                        $duplicity->setState(ImportDuplicityState::DUPLICITY_KEEP_THEIRS);
                        break;
                }

            } else {
                $duplicity->setState(ImportDuplicityState::NO_DUPLICITY);
            }

            $duplicity->setDecideBy($user);
            $duplicity->setDecideAt(new DateTime());
        }

        $this->em->flush();
    }

    public function finish(Import $import): void
    {
        if (!in_array($import->getState(), [ImportState::SIMILARITY_CHECK_CORRECT, ImportState::IMPORTING])) {
            throw new InvalidArgumentException('Wrong import status');
        }

        $queueRepo = $this->em->getRepository(ImportQueue::class);

        foreach ($queueRepo->findBy([
            'import' => $import,
            'state' => ImportQueueState::TO_CREATE,
        ]) as $item) {
            $household = new Household();
            // TODO: fill new HH

            /** @var ImportBeneficiaryDuplicity $acceptedDuplicity */
            $acceptedDuplicity = $item->getAcceptedDuplicity();
            if (null !== $acceptedDuplicity) {
                $this->linkHouseholdToQueue($import, $household, $acceptedDuplicity->getDecideBy());
            } else {
                $this->linkHouseholdToQueue($import, $household, $import->getCreatedBy());
            }
            $this->em->remove($item);
        }

        foreach ($queueRepo->findBy([
            'import' => $import,
            'state' => ImportQueueState::TO_UPDATE,
        ]) as $item) {
            /** @var ImportBeneficiaryDuplicity $acceptedDuplicity */
            $acceptedDuplicity = $item->getAcceptedDuplicity();
            if (null == $acceptedDuplicity) continue;

            // TODO: update

            $this->linkHouseholdToQueue($import, $acceptedDuplicity->getTheirs(), $acceptedDuplicity->getDecideBy());
            $this->em->remove($item);
        }

        foreach ($queueRepo->findBy([
            'import' => $import,
            'state' => ImportQueueState::TO_IGNORE,
        ]) as $item) {
            $this->em->remove($item);
        }

        foreach ($queueRepo->findBy([
            'import' => $import,
            'state' => ImportQueueState::TO_LINK,
        ]) as $item) {
            /** @var ImportBeneficiaryDuplicity $acceptedDuplicity */
            $acceptedDuplicity = $item->getAcceptedDuplicity();
            if (null == $acceptedDuplicity) continue;

            $this->linkHouseholdToQueue($import, $acceptedDuplicity->getTheirs(), $acceptedDuplicity->getDecideBy());
            $this->em->remove($item);
        }
    }

    private function linkHouseholdToQueue(Import $import, Household $household, User $decide): void
    {
        foreach ($household->getBeneficiaries() as $beneficiary) {
            $beneficiaryInImport = new ImportBeneficiary($import, $beneficiary, $decide);
            $this->em->persist($beneficiaryInImport);
        }
    }
}

