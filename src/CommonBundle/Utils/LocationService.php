<?php


namespace CommonBundle\Utils;


use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Form\HouseholdConstraints;
use BeneficiaryBundle\Utils\HouseholdService;
use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use CommonBundle\Entity\Location;
use DistributionBundle\Entity\DistributionData;
use Doctrine\ORM\EntityManagerInterface;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;

class LocationService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var RequestValidator $requestValidator */
    private $requestValidator;

    /**
     * LocationService constructor.
     * @param EntityManagerInterface $entityManager
     * @param RequestValidator $requestValidator
     */
    public function __construct(EntityManagerInterface $entityManager, RequestValidator $requestValidator)
    {
        $this->em = $entityManager;
        $this->requestValidator = $requestValidator;
    }

    /**
     * @param $countryISO3
     * @param array $locationArray
     * @return Location|null|object
     * @throws ValidationException
     */
    public function getOrSaveLocation($countryISO3, array $locationArray)
    {
        $this->requestValidator->validate(
            "location",
            HouseholdConstraints::class,
            $locationArray,
            'any'
        );

        $adm1 = $this->em->getRepository(Adm1::class)->findOneBy([
            "countryISO3" => $countryISO3,
            "name" => $locationArray["adm1"]
        ]);

        if ($adm1 instanceof Adm1)
        {
            if (!array_key_exists("adm2", $locationArray) || null === $locationArray["adm2"] || "" === $locationArray["adm2"])
                return $adm1->getLocation();
            $adm2 = $this->em->getRepository(Adm2::class)->findOneBy([
                "adm1" => $adm1,
                "name" => $locationArray["adm2"]
            ]);

            if ($adm2 instanceof Adm2)
            {
                if (!array_key_exists("adm3", $locationArray) || null === $locationArray["adm3"] || "" === $locationArray["adm3"])
                    return $adm2->getLocation();
                $adm3 = $this->em->getRepository(Adm3::class)->findOneBy([
                    "adm2" => $adm2,
                    "name" => $locationArray["adm3"]
                ]);

                if ($adm3 instanceof Adm3)
                {
                    if (!array_key_exists("adm4", $locationArray) || null === $locationArray["adm4"] || "" === $locationArray["adm4"])
                        return $adm3->getLocation();
                    $adm4 = $this->em->getRepository(Adm4::class)->findOneBy([
                        "adm3" => $adm3,
                        "name" => $locationArray["adm4"]
                    ]);

                    if ($adm4 instanceof Adm4)
                    {
                        return $adm4->getLocation();
                    }

                    return $adm3->getLocation();
                }
                else
                {
                    return $adm2->getLocation();
                }
            }
            else
            {
                return $adm1->getLocation();
            }
        }

        return null;
    }

    /**
     * @param Household $household
     * @return string
     * @throws \Exception
     */
    public function getAdm1(Household $household)
    {
        $location = $household->getLocation();
        if (null !== $location->getAdm1())
        {
            return $location->getAdm1()->getName();
        }
        elseif (null !== $location->getAdm2())
        {
            return $location->getAdm2()->getAdm1()->getName();
        }
        elseif (null !== $location->getAdm3())
        {
            return $location->getAdm3()->getAdm2()->getAdm1()->getName();
        }
        elseif (null !== $location->getAdm4())
        {
            return $location->getAdm4()->getAdm3()->getAdm2()->getAdm1()->getName();
        }
        else
        {
            return "";
        }
    }

    /**
     * @param Household $household
     * @return string
     * @throws \Exception
     */
    public function getAdm2(Household $household)
    {
        $location = $household->getLocation();
        if (null !== $location->getAdm2())
        {
            return $location->getAdm2()->getName();
        }
        elseif (null !== $location->getAdm3())
        {
            return $location->getAdm3()->getAdm2()->getName();
        }
        elseif (null !== $location->getAdm4())
        {
            return $location->getAdm4()->getAdm3()->getAdm2()->getName();
        }
        else
        {
            return "";
        }
    }

    /**
     * @param Household $household
     * @return string
     * @throws \Exception
     */
    public function getAdm3(Household $household)
    {
        $location = $household->getLocation();
        if (null !== $location->getAdm3())
        {
            return $location->getAdm3()->getName();
        }
        elseif (null !== $location->getAdm4())
        {
            return $location->getAdm4()->getAdm3()->getName();
        }
        else
        {
            return "";
        }
    }

    /**
     * @param Household $household
     * @return string
     * @throws \Exception
     */
    public function getAdm4(Household $household)
    {
        $location = $household->getLocation();
        if (null !== $location->getAdm4())
        {
            return $location->getAdm4()->getName();
        }
        else
        {
            return "";
        }
    }

    /**
     * Get the list of all adm1 in the country
     * @param string $countryIso3
     * @return object[]
     */
    public function getAllAdm1(string $countryIso3) {
        $adm1 = $this->em->getRepository(Adm1::class)->findBy(["countryISO3" => $countryIso3]);
        return $adm1;
    }

    /**
     * Get the list of all adm2 linked to the adm1 passed in paramter
     * @param string $IDadm1
     * @return object[]
     */
    public function getAllAdm2(string $IDadm1) {
        $adm1 = $this->em->getRepository(Adm1::class)->findBy(["id" => $IDadm1]);
        $adm2 = $this->em->getRepository(Adm2::class)->findBy(["adm1" => $adm1]);
        return $adm2;
    }

    /**
     * Get the list of all adm3 linked to the adm2 passed in paramter
     * @param string $IDadm2
     * @return object[]
     */
    public function getAllAdm3(string $IDadm2) {
        $adm2 = $this->em->getRepository(Adm2::class)->findBy(["id" => $IDadm2]);
        $adm3 = $this->em->getRepository(Adm3::class)->findBy(["adm2" => $adm2]);
        return $adm3;
    }

    /**
     * Get the list of all adm4 linked to the adm3 passed in paramter
     * @param string $IDadm3
     * @return object[]
     */
    public function getAllAdm4(string $IDadm3) {
        $adm3 = $this->em->getRepository(Adm3::class)->findBy(["id" => $IDadm3]);
        $adm4 = $this->em->getRepository(Adm4::class)->findBy(["adm3" => $adm3]);
        return $adm4;
    }

    /**
     * get the code of location for upcoming distribution
     * @param string $countryIso3
     * @return array
     */
    public function getCodeOfUpcomingDistribution(string $countryIso3) {
        $distributions = $this->em->getRepository(DistributionData::class)->findAll();
        $response = [];
        

        $date = new \Datetime();

        foreach($distributions as $distribution) {
            $upcomingDistributionFind = false;
            if ($distribution->getDateDistribution() > $date) {

                $location = $this->em->getRepository(Location::class)->findOneBy(["id" =>$distribution->getLocation()->getId()]);

                if($location->getAdm1()) 
                {
                    $adm = "adm1";
                    $location_name = $location->getAdm1()->getName();
                    $code = $location->getAdm1()->getCode();
                }
                elseif ($location->getAdm2()) 
                {
                    $adm = "adm2";
                    $location_name = $location->getAdm2()->getName();
                    $code = $location->getAdm2()->getCode();
                }
                elseif ($location->getAdm3()) 
                {
                    $adm = "adm3";
                    $location_name = $location->getAdm3()->getName();
                    $code = $location->getAdm3()->getCode();
                }
                elseif ($location->getAdm4()) 
                {
                    $adm = "adm4";
                    $location_name = $location->getAdm4()->getName();
                    $code = $location->getAdm4()->getCode();
                }

                if(sizeof($response) === 0) {
                    $data = [
                        "code_location" => $code,
                        "adm_level" => $adm, 
                        "distribution" => []                      
                    ];
                    $upcomingDistribution = [
                        "name" => $distribution->getName(),
                        "date" => $distribution->getDateDistribution(),
                        "project_name" => $distribution->getProject()->getName(),
                        "location_name" => $location_name,
                    ];
                    array_push($data['distribution'], $upcomingDistribution);
                    array_push($response, $data);
                } else {
                    foreach($response as &$data) {
                        if($data["code_location"] == $code) {
                            $upcomingDistribution = [
                                "name" => $distribution->getName(),
                                "date" => $distribution->getDateDistribution(),
                                "project_name" => $distribution->getProject()->getName(),
                                "location_name" => $location_name,
                            ];
                            $upcomingDistributionFind = true;
                            array_push($data['distribution'], $upcomingDistribution);
                        } 
                    }
                    if(!$upcomingDistributionFind) {
                        $data = [
                            "code_location" => $code,
                            "adm_level" => $adm, 
                            "distribution" => []                      
                        ];
                        $upcomingDistribution = [
                            "name" => $distribution->getName(),
                            "date" => $distribution->getDateDistribution(),
                            "project_name" => $distribution->getProject()->getName(),
                            
                            "location_name" => $location_name,
                        ];
                        array_push($data['distribution'], $upcomingDistribution);
                        array_push($response, $data);
                    }
                }
                
            } 
        }

        return $response;
    }

}