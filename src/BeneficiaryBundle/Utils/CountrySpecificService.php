<?php


namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\CountrySpecific;
use CommonBundle\Utils\ExportService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CountrySpecificService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /** @var ExportService */
    private $exportCSVService;

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container,
                                ExportService $exportCSVService
    )
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->exportCSVService = $exportCSVService;
    }

    /**
     * @param $countryIso3
     * @return object[]
     */
    public function getAll($countryIso3)
    {
        return $this->em->getRepository(CountrySpecific::class)->findBy(["countryIso3" => $countryIso3]);
    }

    /**
     * @deprecated
     */
    public function create($countryIso3, array $countrySpecificArray)
    {
        $countrySpecific = new CountrySpecific($countrySpecificArray["field"], strtolower($countrySpecificArray["type"]), $countryIso3);
      
        $this->em->persist($countrySpecific);
        $this->em->flush();
 
        return $countrySpecific;
    }

    /**
     * @deprecated
     */
    public function update(CountrySpecific $countrySpecific, $countryIso3, array $countrySpecificArray)
    {
        $countrySpecific->setType($countrySpecificArray["type"])
            ->setFieldString($countrySpecificArray["field"])
            ->setCountryIso3($countryIso3);
      
        $this->em->persist($countrySpecific);
        $this->em->flush();

        $this->em->persist($countrySpecific);
        $this->em->flush();
      
        return $countrySpecific;
    }

    /**
     * @param CountrySpecific $countrySpecific
     * @return bool
     */
    public function delete(CountrySpecific $countrySpecific)
    {
        try {
            $this->em->remove($countrySpecific);
            $this->em->flush();
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * Export all the countries specifics in a CSV file
     * @param string $type
     * @param string $countryIso3
     * @return mixed
     */
    public function exportToCsv(string $type, string $countryIso3)
    {
        $exportableTable = $this->em->getRepository(CountrySpecific::class)->findByCountryIso3($countryIso3);
        return $this->exportCSVService->export($exportableTable, 'country', $type);
    }
}
