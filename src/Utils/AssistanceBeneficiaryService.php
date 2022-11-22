<?php

declare(strict_types=1);

namespace Utils;

use Entity\AbstractBeneficiary;
use Entity\Beneficiary;
use Entity\Community;
use Entity\Household;
use Entity\Institution;
use DateTime;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Enum\AssistanceTargetType;
use Doctrine\ORM\EntityManagerInterface;
use Enum\CacheTarget;
use Workflow\ReliefPackageTransitions;
use Entity\Project;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Class AssistanceBeneficiaryService
 *
 * @package Utils
 */
class AssistanceBeneficiaryService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ExportService */
    private $exportService;

    /**
     * AssistanceBeneficiaryService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ExportService $exportService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ExportService $exportService
    ) {
        $this->em = $entityManager;
        $this->exportService = $exportService;
    }

    /**
     * @param array $objectBeneficiary
     * @param string $type
     * @return mixed
     */
    public function exportToCsv(array $objectBeneficiary, string $type)
    {
        $beneficiaries = [];
        foreach ($objectBeneficiary as $value) {
            $gender = '';

            if ($value['gender'] === '0') {
                $gender = 'Female';
            } else {
                $gender = 'Male';
            }

            array_push($beneficiaries, [
                "English given name" => $value['en_given_name'],
                "English family name" => $value['en_family_name'],
                "Local given name" => $value['local_given_name'],
                "Local family name" => $value['local_family_name'],
                "Gender" => $gender,
                "Status" => $value['status'],
                "Residency status" => $value['residency_status'],
                "Date of birth" => $value['date_of_birth'],
            ]);
        }

        return $this->exportService->export($beneficiaries, 'distributions', $type);
    }
}
