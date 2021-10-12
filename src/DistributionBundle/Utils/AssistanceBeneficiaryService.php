<?php

declare(strict_types=1);

namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\AbstractBeneficiary;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\Institution;
use DateTime;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Enum\AssistanceTargetType;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Enum\ReliefPackageState;
use ProjectBundle\Entity\Project;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class AssistanceBeneficiaryService
 * @package DistributionBundle\Utils
 */
class AssistanceBeneficiaryService
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
     * AssistanceBeneficiaryService constructor.
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
     * Add either a beneficiary of a household(in this case, we assigned the head of the household) to a distribution
     *
     * @param Assistance $assistance
     * @param array      $beneficiariesData
     *
     * @return AssistanceBeneficiary[]
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
            $assistanceBeneficiary = new AssistanceBeneficiary();

            $sameAssistanceBeneficiary = $this->em->getRepository(AssistanceBeneficiary::class)
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
            $assistance = $this->container->get('distribution.assistance_service')->setCommoditiesToNewBeneficiaries($assistance,
                $assistanceBeneficiaries);
        }

        $assistance->setUpdatedOn(new \DateTime());
        $this->em->persist($assistance);

        $this->em->flush();

        return $assistanceBeneficiaries;
    }

    /**
     * Remove beneficiaries from the assistacne
     *
     * @param Assistance $assistance
     * @param array      $beneficiariesData
     *
     * @return void
     * @throws Exception\RemoveBeneficiaryWithReliefException
     */
    public function removeBeneficiaries(Assistance $assistance, array $beneficiariesData): void
    {
        foreach ($beneficiariesData['beneficiaries'] as $id) {
            $this->removeBeneficiaryInDistribution($assistance, $this->em->getRepository(AbstractBeneficiary::class)->find($id), $beneficiariesData);
        }
    }

    /**
     * @param Assistance          $assistance
     * @param AbstractBeneficiary $beneficiary
     * @param                     $deletionData
     *
     * @return bool
     * @throws Exception\RemoveBeneficiaryWithReliefException
     */
    public function removeBeneficiaryInDistribution(Assistance $assistance, AbstractBeneficiary $beneficiary, $deletionData)
    {
        /** @var AssistanceBeneficiary $assistanceBeneficiary */
        $assistanceBeneficiary = $this->em->getRepository(AssistanceBeneficiary::class)->findOneBy(['beneficiary' => $beneficiary->getId(), 'assistance' => $assistance->getId()]);

        if ($assistanceBeneficiary->hasDistributionStarted()) {
            throw new Exception\RemoveBeneficiaryWithReliefException($assistanceBeneficiary->getBeneficiary());
        }

        // Update updatedOn datetime
        $assistance->setUpdatedOn(new DateTime());

        $assistanceBeneficiary->setRemoved(1)
            ->setJustification($deletionData['justification']);

        foreach ($assistanceBeneficiary->getReliefPackages() as $commodity) {
            if (ReliefPackageState::TO_DISTRIBUTE === $commodity->getState())
                $commodity->setState(ReliefPackageState::CANCELED);
        }

        $this->em->persist($assistanceBeneficiary);
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
