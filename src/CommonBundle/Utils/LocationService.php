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
use Doctrine\ORM\EntityManagerInterface;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;

class LocationService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var RequestValidator $requestValidator */
    private $requestValidator;

    public function __construct(EntityManagerInterface $entityManager, RequestValidator $requestValidator)
    {
        $this->em = $entityManager;
        $this->requestValidator = $requestValidator;
    }

    /**
     * @param array $locationArray
     * @return Location|null|object
     * @throws ValidationException
     */
    public function getOrSaveLocation(array $locationArray)
    {
        $this->requestValidator->validate(
            "location",
            HouseholdConstraints::class,
            $locationArray,
            'any'
        );

        $adm1 = $this->em->getRepository(Adm1::class)->findOneBy([
            "countryISO3" => $locationArray["country_iso3"],
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
                    return $adm1->getLocation();
                $adm3 = $this->em->getRepository(Adm3::class)->findOneBy([
                    "adm2" => $adm2,
                    "name" => $locationArray["adm3"]
                ]);

                if ($adm3 instanceof Adm3)
                {
                    if (!array_key_exists("adm4", $locationArray) || null === $locationArray["adm4"] || "" === $locationArray["adm4"])
                        return $adm1->getLocation();
                    $adm4 = $this->em->getRepository(Adm4::class)->findOneBy([
                        "adm3" => $adm3,
                        "name" => $locationArray["adm4"]
                    ]);

                    if ($adm4 instanceof Adm4)
                    {
                        return $adm4->getLocation();
                    }
                }
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
}