<?php

namespace Utils;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Entity\Household;
use Entity\Assistance;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use InvalidArgumentException;
use Entity\Import;
use Exception\ConstraintViolationException;
use InputType\AddHouseholdsToProjectInputType;
use InputType\ProjectCreateInputType;
use InputType\ProjectUpdateInputType;
use Entity\Donor;
use Entity\Project;
use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Entity\User;
use Entity\UserProject;

/**
 * Class ProjectService
 *
 * @package Utils
 */
class ProjectService
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * ProjectService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
    }

    /**
     * @param string $countryIso3
     *
     * @return int
     */
    public function countActive(string $countryIso3): int
    {
        $count = $this->em->getRepository(Project::class)->countActiveInCountry($countryIso3);

        return $count;
    }

    /**
     * @param ProjectCreateInputType $inputType
     * @param User $user
     *
     * @return Project
     * @throws EntityNotFoundException
     */
    public function create(ProjectCreateInputType $inputType, User $user): Project
    {
        $existingProjects = $this->em->getRepository(Project::class)->findBy([
            'name' => $inputType->getName(),
            'countryIso3' => $inputType->getIso3(),
        ]);

        if (!empty($existingProjects)) {
            //TODO think about more systematic solution
            throw new ConstraintViolationException(
                new ConstraintViolation(
                    "Project with name \"{$inputType->getName()}\" already exists. Please choose different one.",
                    null,
                    [],
                    'name',
                    'name',
                    true
                )
            );
        }

        $project = (new Project())
            ->setName($inputType->getName())
            ->setInternalId($inputType->getInternalId())
            ->setStartDate($inputType->getStartDate())
            ->setEndDate($inputType->getEndDate())
            ->setCountryIso3($inputType->getIso3())
            ->setTarget($inputType->getTarget())
            ->setNotes($inputType->getNotes())
            ->setSectors($inputType->getSectors())
            ->setProjectInvoiceAddressLocal($inputType->getProjectInvoiceAddressLocal())
            ->setProjectInvoiceAddressEnglish($inputType->getProjectInvoiceAddressEnglish())
            ->setAllowedProductCategoryTypes($inputType->getAllowedProductCategoryTypes());

        foreach ($inputType->getDonorIds() as $id) {
            $donor = $this->em->getRepository(Donor::class)->find($id);
            if ($donor instanceof Donor) {
                $project->addDonor($donor);
            } else {
                throw new EntityNotFoundException("Donor with ID #$id does not exists.");
            }
        }

        $this->em->persist($project);
        $this->em->flush();

        $this->addUser($project, $user);

        return $project;
    }

    /**
     * @param Project $project
     * @param ProjectUpdateInputType $inputType
     *
     * @return Project
     * @throws EntityNotFoundException
     */
    public function update(Project $project, ProjectUpdateInputType $inputType)
    {
        $existingProjects = $this->em->getRepository(Project::class)->findBy([
            'name' => $inputType->getName(),
            'countryIso3' => $inputType->getIso3(),
        ]);

        if (!empty($existingProjects) && $existingProjects[0]->getId() !== $project->getId()) {
            throw new ConstraintViolationException(
                new ConstraintViolation(
                    "Project with name \"{$inputType->getName()}\" already exists. Please choose different one.",
                    null,
                    [],
                    'name',
                    'name',
                    true
                )
            );
        }

        $project
            ->setName($inputType->getName())
            ->setInternalId($inputType->getInternalId())
            ->setStartDate($inputType->getStartDate())
            ->setEndDate($inputType->getEndDate())
            ->setCountryIso3($inputType->getIso3())
            ->setTarget($inputType->getTarget())
            ->setNotes($inputType->getNotes())
            ->setSectors($inputType->getSectors())
            ->setProjectInvoiceAddressLocal($inputType->getProjectInvoiceAddressLocal())
            ->setProjectInvoiceAddressEnglish($inputType->getProjectInvoiceAddressEnglish())
            ->setAllowedProductCategoryTypes($inputType->getAllowedProductCategoryTypes());

        $project->removeDonors();
        foreach ($inputType->getDonorIds() as $id) {
            $donor = $this->em->getRepository(Donor::class)->find($id);
            if ($donor instanceof Donor) {
                $project->addDonor($donor);
            } else {
                throw new EntityNotFoundException("Donor with ID #$id does not exists.");
            }
        }

        $this->em->persist($project);
        $this->em->flush();

        return $project;
    }

    /**
     * @param Project $project
     * @param AddHouseholdsToProjectInputType $inputType
     */
    public function addHouseholds(Project $project, AddHouseholdsToProjectInputType $inputType): void
    {
        foreach ($inputType->getHouseholdIds() as $householdId) {
            $household = $this->em->getRepository(Household::class)->find($householdId);

            if (!$household instanceof Household) {
                throw new InvalidArgumentException("Household with id $householdId not found.");
            }

            if (!$household->getProjects()->contains($project)) {
                $household->addProject($project);
            }
        }

        $this->em->flush();
    }

    /**
     * @param Project $project
     * @param User $user
     */
    public function addUser(Project $project, User $user)
    {
        $right = $user->getRoles();
        if ($right[0] !== "ROLE_ADMIN") {
            $userProject = new UserProject();
            $userProject->setUser($user)
                ->setProject($project)
                ->setRights($right[0]);

            $this->em->persist($userProject);
            $this->em->flush();
        }
    }

    public function isDeletable(Project $project): bool
    {
        /** @var Paginator $assistance */
        $assistances = $this->em->getRepository(Assistance::class)->findByProject($project);

        return 0 === count($assistances) || $this->checkIfAllDistributionClosed($assistances);
    }

    /**
     * @param Project $project
     * @return void
     * @throws error if one or more distributions prevent the project from being deleted
     */
    public function delete(Project $project)
    {
        /** @var Paginator $assistance */
        $assistance = $this->em->getRepository(Assistance::class)->findByProject($project);

        if (0 === $assistance->count()) {
            $imports = $this->em->getRepository(Import::class)->findByProject($project);
            /** @var Import $import */
            foreach ($imports as $import) {
                if ($import->getProjects()->count() == 1) {
                    $this->em->remove($import);
                } else {
                    $import->removeProject($project);
                }
            }
            foreach ($project->getSectors()->getValues() as $projectSector) {
                $this->em->remove($projectSector);
            }
            $this->em->remove($project);
        } else {
            if (!$this->checkIfAllDistributionClosed($assistance)) {
                throw new Exception("You can't delete this project as it has an unfinished distribution");
            } else {
                try {
                    foreach ($assistance as $distributionDatum) {
                        $distributionDatum->setArchived(1);
                    }

                    $project->setArchived(true);
                    $this->em->persist($project);
                } catch (Exception $error) {
                    throw new Exception("Error archiving project");
                }
            }
        }
        $this->em->flush();
    }

    /**
     * Check if all distributions allow for the project to be deleted
     *
     * @param Assistance[] $assistance
     * @return bool
     */
    private function checkIfAllDistributionClosed(iterable $assistances)
    {
        foreach ($assistances as $distributionDatum) {
            if (!$distributionDatum->getArchived() && !$distributionDatum->getCompleted()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Export all projects of the country in the CSV file
     *
     * @param $countryIso3
     * @param string $type
     * @return mixed
     */
    public function exportToCsv($countryIso3, string $type)
    {
        $exportableTable = $this->em->getRepository(Project::class)->getAllOfCountry($countryIso3);

        return $this->container->get('export_csv_service')->export($exportableTable, 'projects', $type);
    }
}
