<?php

declare(strict_types=1);

namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\Institution;
use DateTime;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Enum\AssistanceTargetType;
use Doctrine\ORM\EntityManagerInterface;
use ProjectBundle\Entity\Project;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class DistributionBeneficiaryService
 * @package DistributionBundle\Utils
 */
class DistributionBeneficiaryService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var ContainerInterface $container */
    private $container;


    /**
     * DistributionBeneficiaryService constructor.
     * @param EntityManagerInterface $entityManager
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer, ValidatorInterface $validator, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
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
    public function getDistributionBeneficiaries(Assistance $assistance)
    {
        $distributionBeneficiaries = $this->em->getRepository(DistributionBeneficiary::class)->findBy(['assistance' => $assistance]);
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
        $distributionBeneficiaries = $this->em->getRepository(DistributionBeneficiary::class)->findAssignable($assistance);
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
     * Add either a beneficiary of a household(in this case, we assigned the head of the household) to a distribution
     *
     * @param Assistance $assistance
     * @param array      $beneficiariesData
     *
     * @return DistributionBeneficiary[]
     * @throws \Exception
     */
    public function addBeneficiaries(Assistance $assistance, array $beneficiariesData): array
    {
        $beneficiariesArray = $beneficiariesData['beneficiaries'];
        $validBNFs = [];

        if (empty($beneficiariesArray)) {
            return [];
        }

        if (!isset($beneficiariesData['justification']) || empty($beneficiariesData['justification'])) {
            throw new \Exception('Justification missing.');
        }

        // id validation
        foreach ($beneficiariesArray as $beneficiaryArray) {

            if (!isset($beneficiaryArray["id"])) {
                throw new \Exception("Beneficiary ID missing.");
            }

            // everything else is duplicity crap
            $bnfId = (int) $beneficiaryArray["id"];

            switch ($assistance->getTargetType()) {
                case AssistanceTargetType::HOUSEHOLD:
                    $householdMember = $this->em->getRepository(Beneficiary::class)->find($bnfId);
                    $household = $householdMember->getHousehold();
                    if (!$household instanceof Household) {
                        throw new \Exception("Household {$bnfId} was not found.");
                    }
                    $beneficiary = $this->em->getRepository(Beneficiary::class)->getHeadOfHousehold($household);
                    break;
                case AssistanceTargetType::INDIVIDUAL:
                    $beneficiary = $this->em->getRepository(Beneficiary::class)->find($bnfId);
                    if (!$beneficiary instanceof Beneficiary) {
                        throw new \Exception("Beneficiary {$bnfId} was not found.");
                    }
                    break;
                case AssistanceTargetType::COMMUNITY:
                    $beneficiary = $this->em->getRepository(Community::class)->find($bnfId);
                    if (!$beneficiary instanceof Community) {
                        throw new \Exception("Community {$bnfId} was not found.");
                    }
                    break;
                case AssistanceTargetType::INSTITUTION:
                    $beneficiary = $this->em->getRepository(Institution::class)->find($bnfId);
                    if (!$beneficiary instanceof Institution) {
                        throw new \Exception("Institution {$bnfId} was not found.");
                    }
                    break;
                default:
                    throw new \Exception("The type of the distribution is undefined.");
            }
            $validBNFs[] = $beneficiary;
        }

        $assistanceBeneficiaries = [];

        foreach ($validBNFs as $beneficiary) {
            $assistanceBeneficiary = new DistributionBeneficiary();

            $sameAssistanceBeneficiary = $this->em->getRepository(DistributionBeneficiary::class)
                ->findOneBy(['beneficiary' => $beneficiary, 'assistance' => $assistance]);

            // $beneficiariesArray contains at least the country so a unique beneficiary would be a size of 2
            if ($sameAssistanceBeneficiary && sizeof($validBNFs) <= 2 && !$sameAssistanceBeneficiary->getRemoved()) {
                throw new \Exception("Beneficiary/household {$beneficiary->getId()} is already part of the distribution", Response::HTTP_BAD_REQUEST);
            } elseif ($sameAssistanceBeneficiary && sizeof($validBNFs) <= 2 && $sameAssistanceBeneficiary->getRemoved()) {
                $sameAssistanceBeneficiary->setRemoved(0)
                    ->setJustification($beneficiariesData['justification']);
                $this->em->persist($sameAssistanceBeneficiary);
            } elseif (!$sameAssistanceBeneficiary) {
                $assistanceBeneficiary->setAssistance($assistance)
                    ->setBeneficiary($beneficiary)
                    ->setRemoved(0)
                    ->setJustification($beneficiariesData['justification']);
                $this->em->persist($assistanceBeneficiary);
                array_push($assistanceBeneficiaries, $assistanceBeneficiary);
            }
        }

        if ($assistance->getValidated()) {
            $assistance = $this->container->get('distribution.distribution_service')->setCommoditiesToNewBeneficiaries($assistance,
                $assistanceBeneficiaries);
        }

        $assistance->setUpdatedOn(new \DateTime());
        $this->em->persist($assistance);

        $this->em->flush();

        return $assistanceBeneficiaries;
    }

    /**
     * @param Assistance $assistance
     * @param Beneficiary $beneficiary
     * @return bool
     */
    public function removeBeneficiaryInDistribution(Assistance $assistance, Beneficiary $beneficiary, $deletionData)
    {
        $distributionBeneficiary = $this->em->getRepository(DistributionBeneficiary::class)->findOneBy(['beneficiary' => $beneficiary->getId(), 'assistance' => $assistance->getId()]);

        // Update updatedOn datetime
        $assistance->setUpdatedOn(new DateTime());

        $distributionBeneficiary->setRemoved(1)
            ->setJustification($deletionData['justification']);
        $this->em->persist($distributionBeneficiary);
        $this->em->flush();
        return true;
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
    public function getAllBeneficiariesInProject(Project $project, string $target)
    {
        return $this->em->getRepository(Beneficiary::class)->getAllOfProject($project->getId(), $target);
    }
}
