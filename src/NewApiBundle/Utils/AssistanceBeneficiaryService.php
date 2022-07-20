<?php

declare(strict_types=1);

namespace NewApiBundle\Utils;

use NewApiBundle\Entity\AbstractBeneficiary;
use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Entity\Community;
use NewApiBundle\Entity\Household;
use NewApiBundle\Entity\Institution;
use DateTime;
use NewApiBundle\Entity\Assistance;
use NewApiBundle\Entity\AssistanceBeneficiary;
use NewApiBundle\Enum\AssistanceTargetType;
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
 * @package NewApiBundle\Utils
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
}
