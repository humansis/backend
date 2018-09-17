<?php

namespace ProjectBundle\Utils;

use BeneficiaryBundle\Entity\ProjectBeneficiary;
use BeneficiaryBundle\Entity\Household;
use DistributionBundle\Entity\DistributionData;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use ProjectBundle\Entity\Donor;
use ProjectBundle\Entity\Sector;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use ProjectBundle\Entity\Project;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UserBundle\Entity\User;
use UserBundle\Entity\UserProject;

class ProjectService
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * ProjectService constructor.
     * @param EntityManagerInterface $entityManager
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer, ValidatorInterface $validator , ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->container = $container;

    }

    /**
     * Get all projects
     *
     * @param $countryIso3
     * @param User $user
     * @return array
     */
    public function findAll($countryIso3, User $user)
    {
        if (
            !$user->hasRole("ROLE_COUNTRY_MANAGER")
            && !$user->hasRole("ROLE_REGIONAL_MANAGER")
            && !$user->hasRole("ROLE_ADMIN")
        )
        {
            $projects = $this->em->getRepository(Project::class)->getAllOfUser($user);
        }
        else
        {
            $projects = $this->em->getRepository(Project::class)->getAllOfCountry($countryIso3);

        }
        $houseHoldsRepository = $this->em->getRepository(Household::class);
        foreach($projects as $project){
            $project->setNumberOfHouseholds($houseHoldsRepository->countByProject($project)[1]);
            $this->em->merge($project);
        }
        $this->em->flush();
        return $projects;
    }

    /**
     * @param string $iso3
     * @return mixed
     */
    public function countAll(string $iso3)
    {
        $count = $this->em->getRepository(Project::class)->count(['iso3' => $iso3, 'archived' => 0]);
        return $count;
    }

    /**
     * Create a project
     *
     * @param $countryISO3
     * @param array $projectArray
     * @param User $user
     * @return Project
     * @throws \Exception
     */
    public function create($countryISO3, array $projectArray, User $user)
    {
        /** @var Project $project */
        $newProject = $this->serializer->deserialize(json_encode($projectArray), Project::class, 'json');
        $project = new Project();
        $project->setName($newProject->getName())
                ->setName($newProject->getName())
                ->setStartDate($newProject->getStartDate())        
                ->setEndDate($newProject->getEndDate())
                ->setIso3($countryISO3);

        $errors = $this->validator->validate($project);
        if (count($errors) > 0)
        {
            $errorsArray = [];
            foreach ($errors as $error)
            {
                $errorsArray[] = $error->getMessage();
            }
            throw new \Exception(json_encode($errorsArray), Response::HTTP_BAD_REQUEST);
        }

        $sectors = $newProject->getSectors();
        if (null !== $sectors)
        {
            $project->getSectors()->clear();
            /** @var Sector $sector */
            foreach ($sectors as $sector)
            {
                $sectorTmp = $this->em->getRepository(Sector::class)->find($sector);
                if ($sectorTmp instanceof Sector)
                    $project->addSector($sectorTmp);
            }
        }

        $donors = $newProject->getDonors();
        if (null !== $donors)
        {
            $project->getDonors()->clear();
            /** @var Donor $donor */
            foreach ($donors as $donor)
            {
                $donorTmp = $this->em->getRepository(Donor::class)->find($donor);
                if ($donorTmp instanceof Donor)
                    $project->addDonor($donorTmp);
            }
        }

        $this->em->persist($project);
        $this->em->flush();

        $this->addUser($project, $user, UserProject::RIGHT_MANAGER);

        return $project;
    }

    /**
     * @param Project $project
     * @param array $projectArray
     * @return Project
     * @throws \Exception
     */
    public function edit(Project $project, array $projectArray)
    {
        /** @var Project $editedProject */
        $editedProject = $this->serializer->deserialize(json_encode($projectArray), Project::class, 'json');
        $project->setName($editedProject->getName())
                ->setStartDate($editedProject->getStartDate())        
                ->setEndDate($editedProject->getEndDate());

        $sectors = $editedProject->getSectors();
        if (null !== $sectors)
        {
            $sectors = clone $editedProject->getSectors();
            $project->removeSectors();
            /** @var Sector $sector */
            foreach ($sectors as $sector)
            {
                $sectorTmp = $this->em->getRepository(Sector::class)->find($sector);
                if ($sectorTmp instanceof Sector)
                    $project->addSector($sectorTmp);
            }
        }

        $donors = $editedProject->getDonors();

        if (null !== $donors)
        {
            $donors = clone $editedProject->getDonors();
            $project->removeDonors();
            /** @var Donor $donor */
            foreach ($donors as $donor)
            {
                $donorTmp = $this->em->getRepository(Donor::class)->find($donor);
                if ($donorTmp instanceof Donor)
                    $project->addDonor($donorTmp);
            }
        }

        $errors = $this->validator->validate($project);
        if (count($errors) > 0)
        {
            $errorsArray = [];
            foreach ($errors as $error)
            {
                $errorsArray[] = $error->getMessage();
            }
            throw new \Exception(json_encode($errorsArray), Response::HTTP_BAD_REQUEST);
        }

        $this->em->merge($project);
        try{
            $this->em->flush();

        } catch (\Exception $e){
            return false;
        }

        return $project;
    }

    /**
     * @param Project $project
     * @param User $user
     * @param int $right
     */
    public function addUser(Project $project, User $user, int $right)
    {
        $userProject = new UserProject();
        $userProject->setUser($user)
            ->setProject($project)
            ->setRights($right);

        $this->em->persist($userProject);
        $this->em->flush();
    }

    /**
     * @param Project $project
     * @return bool
     */
    public function delete(Project $project)
    {
        $distributionData = $this->em->getRepository(DistributionData::class)->findByProject($project);
        if (!empty($distributionData))
            $this->archived($project);

        $userProjects = $this->em->getRepository(UserProject::class)->findBy(["project" => $project]);
        if (!empty($userProjects))
        {
            foreach ($userProjects as $userProject)
            {
                $this->em->remove($userProject);
            }
        }
        $this->em->flush();

        try
        {
            $this->em->remove($project);
            $this->em->flush();
        }
        catch (\Exception $exception)
        {
            return false;
        }

        return true;
    }

    /**
     * @param Project $project
     * @return bool
     */
    public function archived(Project $project)
    {
        $project->setArchived(1);

        $this->em->persist($project);
        $this->em->flush();

        return true;
    }

    /**
     * Export all projects of the country in the CSV file
     * @param $countryIso3
     * @param string $type
     * @return mixed
     */
    public function exportToCsv($countryIso3, string $type) {


        $exportableTable = $this->em->getRepository(Project::class)->getAllOfCountry($countryIso3);

        return $this->container->get('export_csv_service')->export($exportableTable, 'projects', $type);

    }
}
