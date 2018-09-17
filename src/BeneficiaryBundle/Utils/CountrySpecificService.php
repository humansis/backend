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
     * @param $countryIso3
     * @param array $countrySpecificArray
     * @return CountrySpecific
     */
    public function create($countryIso3, array $countrySpecificArray)
    {
        $countrySpecific = new CountrySpecific($countrySpecificArray["field"], strtolower($countrySpecificArray["type"]), $countryIso3);

        $this->em->persist($countrySpecific);
        $this->em->flush();

        return $countrySpecific;
    }

    /**
     * @param CountrySpecific $countrySpecific
     * @param $countryIso3
     * @param array $countrySpecificArray
     * @return CountrySpecific
     */
    public function update(CountrySpecific $countrySpecific, $countryIso3, array $countrySpecificArray)
    {
        $countrySpecific->setType($countrySpecificArray["type"])
            ->setFieldString($countrySpecificArray["field"])
            ->setCountryIso3($countryIso3);

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
     * @return mixed
     */
    public function exportToCsv(string $type) {

        $exportableTable = $this->em->getRepository(CountrySpecific::class)->findAll();
        return $this->container->get('export_csv_service')->export($exportableTable, 'country', $type);

    }

}