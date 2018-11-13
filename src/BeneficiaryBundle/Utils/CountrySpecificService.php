<?php


namespace BeneficiaryBundle\Utils;


use BeneficiaryBundle\Entity\CountrySpecific;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CountrySpecificService
{
    /** @var EntityManagerInterface $em */
    private $em;


    /** @var ContainerInterface $container */
    private $container;



    public function __construct(EntityManagerInterface $entityManager , ContainerInterface $container )
    {
        $this->em = $entityManager;
        $this->container = $container;

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
     * @param CountrySpecific $countrySpecific
     * @return bool
     */
    public function delete(CountrySpecific $countrySpecific)
    {
        try
        {
            $this->em->remove($countrySpecific);
            $this->em->flush();
        }
        catch (\Exception $exception)
        {
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
    public function exportToCsv(string $type, string $countryIso3) {

        $exportableTable = $this->em->getRepository(CountrySpecific::class)->findByCountryIso3($countryIso3);
        return $this->container->get('export_csv_service')->export($exportableTable, 'country', $type);

    }

}