<?php


namespace BeneficiaryBundle\Utils\DataTreatment;


use BeneficiaryBundle\Utils\BeneficiaryService;
use BeneficiaryBundle\Utils\HouseholdService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Container;

abstract class AbstractTreatment implements InterfaceTreatment
{

    /** @var EntityManagerInterface $em */
    protected $em;

    /** @var HouseholdService $householdService */
    protected $householdService;

    /** @var BeneficiaryService $beneficiaryService */
    protected $beneficiaryService;

    /** @var $token */
    protected $token;

    /** @var Container $container */
    protected $container;

    public function __construct(
        EntityManagerInterface $entityManager,
        HouseholdService $householdService,
        BeneficiaryService $beneficiaryService,
        Container $container,
        $token
    )
    {
        $this->em = $entityManager;
        $this->householdService = $householdService;
        $this->beneficiaryService = $beneficiaryService;
        $this->container = $container;
        $this->token = $token;
    }

    /**
     * @param $step
     * @param array $listHouseholdsArray
     * @throws \Exception
     */
    protected function getFromCache($step, array &$listHouseholdsArray)
    {
        if (null === $this->token)
            return;

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data/' . $this->token;
        if (!is_dir($dir_var))
            mkdir($dir_var);
        $dir_file = $dir_var . '/' . $step;
        if (!is_file($dir_file))
            return;
        $fileContent = file_get_contents($dir_file);
        $householdsCached = json_decode($fileContent, true);
        foreach ($householdsCached as $householdCached)
        {
            $listHouseholdsArray[] = $householdCached;
        }
    }
}