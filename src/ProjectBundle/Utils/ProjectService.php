<?php

namespace ProjectBundle\Utils;

use BeneficiaryBundle\Entity\ProjectBeneficiary;
use DistributionBundle\Entity\DistributionData;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use ProjectBundle\Entity\Donor;
use ProjectBundle\Entity\Sector;
use Symfony\Component\HttpFoundation\Response;
use ProjectBundle\Entity\Project;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UserBundle\Entity\User;
use UserBundle\Entity\UserProject;

class ProjectService
{
    protected $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var ValidatorInterface $validator */
    private $validator;

    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer, ValidatorInterface $validator)
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    /**
     * Get all projects
     *
     * @return array
     */
    public function findAll()
    {
        return $this->em->getRepository(Project::class)->findByArchived(0);
    }

    /**
     * Create a project
     *
     * @param array $projectArray
     * @return Project
     * @throws \Exception
     */
    public function create(array $projectArray, User $user)
    {
        /** @var Project $project */
        $project = $this->serializer->deserialize(json_encode($projectArray), Project::class, 'json');

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

        $sectors = $project->getSectors();
        if (null !== $sectors)
        {
            $project->cleanSectors();
            /** @var Sector $sector */
            foreach ($sectors as $sector)
            {
                $sectorTmp = $this->em->getRepository(Sector::class)->find($sector);
                if ($sectorTmp instanceof Sector)
                    $project->addSector($sectorTmp);
            }
        }

        $donors = $project->getDonors();
        if (null !== $donors)
        {
            $project->cleanDonors();
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

        $editedProject->setId($project->getId());

        $errors = $this->validator->validate($editedProject);
        if (count($errors) > 0)
        {
            $errorsArray = [];
            foreach ($errors as $error)
            {
                $errorsArray[] = $error->getMessage();
            }
            throw new \Exception(json_encode($errorsArray), Response::HTTP_BAD_REQUEST);
        }

        $this->em->merge($editedProject);
        $this->em->flush();

        return $editedProject;
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
        $projectBeneficiary = $this->em->getRepository(ProjectBeneficiary::class)->findByProject($project);
        if (!empty($projectBeneficiary))
            $this->archived($project);
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
}
