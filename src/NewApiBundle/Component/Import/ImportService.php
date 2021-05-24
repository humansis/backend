<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use NewApiBundle\Component\Import\ValueObject\ImportStatisticsValueObject;
use NewApiBundle\Entity\Import;
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
}

