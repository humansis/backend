<?php

declare(strict_types=1);

namespace DistributionBundle\Utils;

use NewApiBundle\Entity\AbstractBeneficiary;
use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Entity\Community;
use NewApiBundle\Entity\Household;
use NewApiBundle\Entity\Institution;
use DateTime;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Enum\AssistanceTargetType;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Enum\CacheTarget;
use NewApiBundle\Workflow\ReliefPackageTransitions;
use NewApiBundle\Entity\Project;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Class AssistanceBeneficiaryService
 * @package DistributionBundle\Utils
 */
class AssistanceBeneficiaryService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * AssistanceBeneficiaryService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface     $container
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ContainerInterface     $container
    ) {
        $this->em = $entityManager;
        $this->container = $container;
    }

    /**
     * Get all beneficiaries from a distribution
     *
     * @param Assistance $assistance
     * @return array
     */
    public function getBeneficiaries(Assistance $assistance)
    {
        $beneficiaries = $this->em->getRepository(Beneficiary::class)->getAllofDistribution($assistance);
        return $beneficiaries;
    }

    /**
     * Get all distribution beneficiaries from a distribution
     *
     * @param Assistance $assistance
     * @return array
     */
    public function getAssistanceBeneficiaries(Assistance $assistance)
    {
        $distributionBeneficiaries = $this->em->getRepository(AssistanceBeneficiary::class)->findByAssistance($assistance);
        return $distributionBeneficiaries;
    }

    /**
     * Get all distribution beneficiaries from a distribution
     *
     * @param Assistance $assistance
     * @return array
     */
    public function getActiveAssistanceBeneficiaries(Assistance $assistance)
    {
        $distributionBeneficiaries = $this->em->getRepository(AssistanceBeneficiary::class)->findActiveByAssistance($assistance);
        return $distributionBeneficiaries;
    }

    /**
     * Get distribution beneficiaries without booklets
     *
     * @param Assistance $assistance
     * @return array
     */
    public function getDistributionAssignableBeneficiaries(Assistance $assistance)
    {
        $distributionBeneficiaries = $this->em->getRepository(AssistanceBeneficiary::class)->findAssignable($assistance);
        return $distributionBeneficiaries;
    }


    /**
     * Get random beneficiaries from a distribution
     *
     * @param Assistance $assistance
     * @param Int $numberRandomBeneficiary
     * @return array
     */
    public function getRandomBeneficiaries(Assistance $assistance, Int $numberRandomBeneficiary)
    {
        $listReceivers = $this->em->getRepository(Beneficiary::class)->getNotRemovedofDistribution($assistance);

        if (sizeof($listReceivers) < $numberRandomBeneficiary) {
            return $listReceivers;
        }


        $randomIds = array_rand($listReceivers, $numberRandomBeneficiary);

        if (gettype($randomIds) == 'integer') {
            return [$listReceivers[$randomIds]];
        }

        $randomReceivers = array();
        foreach ($randomIds as $id) {
            array_push($randomReceivers, $listReceivers[$id]);
        }

        return $randomReceivers;
    }

    /**
     * @param array $objectBeneficiary
     * @param string $type
     * @return mixed
     */
    public function exportToCsv(array $objectBeneficiary, string $type)
    {
        $beneficiaries = array();
        foreach ($objectBeneficiary as $value) {
            $gender = '';

            if ($value['gender'] === '0') {
                $gender = 'Female';
            } else {
                $gender = 'Male';
            }

            array_push($beneficiaries, [
                "English given name" => $value['en_given_name'],
                "English family name"=> $value['en_family_name'],
                "Local given name" => $value['local_given_name'],
                "Local family name"=> $value['local_family_name'],
                "Gender" => $gender,
                "Status" => $value['status'],
                "Residency status" => $value['residency_status'],
                "Date of birth" => $value['date_of_birth']
            ]);
        }
        return $this->container->get('export_csv_service')->export($beneficiaries, 'distributions', $type);
    }

    /**
     * Get all beneficiaries in a selected project
     *
     * @param Project $project
     * @param string $target
     * @return array
     */
    public function getAllBeneficiariesInProject(Project $project, string $target): array
    {
        return $this->em->getRepository(Beneficiary::class)->getAllOfProject($project, $target);
    }
}
