<?php

namespace BeneficiaryBundle\Utils\ImportProvider\KHM;

use BeneficiaryBundle\Entity\Address;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\HouseholdLocation;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\Profile;
use BeneficiaryBundle\Utils\ImportProvider\DefaultAPIProvider;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use CommonBundle\Entity\OrganizationServices;
use CommonBundle\Entity\Service;
use CommonBundle\Utils\LocationService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class KHMApiProvider
 * @package BeneficiaryBundle\Utils\ImportProvider
 */
class KHMIDPoorAPIProvider extends DefaultAPIProvider
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /** @var ValidatorInterface $validator */
    private $validator;

    /**
     * @var string
     */
    private $url = "http://hub.cam-monitoring.info:8383/api/idpoor8/";

    /** @var LocationService $locationService */
    private $locationService;

    /**
     * KHMApiProvider constructor.
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface $validator
     * @param ContainerInterface $container
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        ContainerInterface $container
    ) {
        $this->em = $entityManager;
        $this->validator = $validator;
        $this->container= $container;
        $this->locationService = $this->container->get('location_service');
    }

    /**
     * Import beneficiaries from API
     * @param string $countryIso3
     * @param array $params
     * @param Project $project
     * @return array
     * @throws \Exception
     */
    public function importData(string $countryIso3, array $params, Project $project)
    {
        if (key_exists('locationCode (KHXXXXXX)', $params)) {
            $householdsArray = $this->importByCountryCode($params);

            if (array_key_exists('error', $householdsArray) && array_key_exists('message', $householdsArray)) {
                throw new \Exception($householdsArray['message']);
            }

            return $this->parseData($householdsArray, $countryIso3, $project);
        } elseif (!key_exists('locationCode (KHXXXXXX)', $params)) {
            throw new \Exception("Missing locationCode in the request");
        } else {
            throw new \Exception("Error occured with the request");
        }
    }

    /**
     * @param array $params
     * @return array|string
     * @throws \Exception
     */
    private function importByCountryCode(array $params)
    {
        $locationCode = $params['locationCode (KHXXXXXX)'];
        $locationCodeNum = substr($locationCode, 2);

        $organizationIDPoor = $this->em->getRepository(OrganizationServices::class)->findOneByService("IDPoor API");

        if (! $organizationIDPoor->getEnabled()) {
            throw new \Exception("This service is not enabled for the organization");
        }

        $email = urlencode($organizationIDPoor->getParameterValue('email'));
        $token = $organizationIDPoor->getParameterValue('token');

        if (!$email || !$token) {
            throw new \Exception("This service has no parameters specified");
        }

        // $route = $locationCodeNum . ".json?email=james.happell%40peopleinneed.cz&token=K45nDocxQ5sEFfqSWwDm-2DxskYEDYFe";

        $route = $locationCodeNum . ".json?email=" . $email . "&token=" . $token;

        try {
            $villages = $this->sendRequest("GET", $route);
            if (array_key_exists('error', $villages) && array_key_exists('message', $villages)) {
                return $villages;
            }
            
            $beneficiariesArray = array();

            foreach ($villages as $village) {
                // Save adm4 village for Cambodia
                $location = $this->saveAdm4($village, $locationCode);

                foreach ($village['HouseholdMembers'] as $householdMember) {
                    // Name
                    $fullName = null;
                    for ($i = 0; $i < strlen($householdMember['MemberName']); $i++) {
                        if ($householdMember['MemberName'][$i] == ' ') {
                            $fullName = explode(' ', $householdMember['MemberName']);
                        }
                    }
                    if ($fullName) {
                        $localGivenName = $fullName[0];
                        $localFamilyName = $fullName[1];
                    } else {
                        $localGivenName = ' ';
                        $localFamilyName = $householdMember['MemberName'];
                    }
                    // Status
                    $headOfHousehold = ($householdMember['RelationshipToHH'] == "Head of Household") ? 1: 0;
                    //Sex
                    $sex = ($householdMember['Sex'] == 'Man') ? 1 : 0;

                    array_push(
                        $beneficiariesArray,
                        array(
                            'equityCardNo' => $householdMember['EquityCardNo'],
                            'status' => $headOfHousehold,
                            'residencyStatus' => 'resident',
                            'localGivenName' => $localGivenName,
                            'localFamilyName' => $localFamilyName,
                            'IDPoor' => $householdMember['PovertyLevel'],
                            'gender' => $sex,
                            'dateOfBirth' => $householdMember['YearOfBirth'] . '-01-01',
                            'location' => $location
                        )
                    );
                }
            }

            // Group beneficiaries by household
            $householdsArray = array();
            foreach ($beneficiariesArray as $beneficiary) {
                $householdsArray[$beneficiary['equityCardNo']][] = $beneficiary;
            }

            return $householdsArray;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param array $householdsArray
     * @param string $countryIso3
     * @param Project $project
     * @return array
     * @throws \Exception
     */
    public function parseData(array $householdsArray, string $countryIso3, Project $project)
    {
        $countNew = 0;
        $countUpdated = 0;
        $countBeneficiaries = 0;
        $householdsImported = array();

        foreach ($householdsArray as $beneficiariesInHousehold) {
            try {
                $hhArray = $this->createAndInitHousehold($beneficiariesInHousehold[0], $project);
                $household = $hhArray["household"];
                if ($hhArray["status"] === "create") {
                    $countNew++;
                } elseif ($hhArray["status"] === "update") {
                    $countUpdated++;
                }
            } catch (\Exception $e) {
                throw $e;
            }
            
            try {
                $countBeneficiaries += $this->insertBeneficiaries($household, $beneficiariesInHousehold);
            } catch (\Exception $e) {
                throw $e;
            }

            $this->em->flush();
            array_push($householdsImported, $household->getId());
        }

        if ($countNew + $countUpdated > 0) {
            return ['message' => $countNew . " households created and " . $countUpdated . " updated (" . $countBeneficiaries . " beneficiaries)", "households" => $householdsImported];
        } else {
            return ['exist' => 'All beneficiaries with this location code are already inserted for this project'];
        }
    }

    /**
     * @param array $beneficiary
     * @param Project $project
     * @return array
     */
    private function createAndInitHousehold(array $beneficiary, Project $project)
    {
        // Check if household already exists by searching one of its beneficiaries
        $dateOfBirth = new DateTime($beneficiary['dateOfBirth']);
        $localFamilyName = $beneficiary['localFamilyName'];
        $localGivenName = $beneficiary['localGivenName'];
        $status = $beneficiary['status'];
        $gender = $beneficiary['gender'];
        
        $existingBeneficiary = $this->em->getRepository(Beneficiary::class)->findOneBy(
            [
                'localGivenName' => $localGivenName,
                'localFamilyName' => $localFamilyName,
                'gender' => $gender,
                'status' => $status,
                'dateOfBirth' => $dateOfBirth
            ]
        );
        
        // If a beneficiary exists, household already exists
        if ($existingBeneficiary) {
            $status = "update";
            $household = $existingBeneficiary->getHousehold();
            $projects = $household->getProjects();
            if (! $projects->contains($project)) {
                $household->addProject($project);
            }
        } else {
            $status = "create";
            /** @var Household $household */
            $household = new Household();
            $household->addProject($project);
        }

        $address = new Address();
        $address->setLocation($beneficiary['location']);
        $householdLocation = new HouseholdLocation();
        $householdLocation->setLocationGroup(HouseholdLocation::LOCATION_GROUP_CURRENT)
            ->setType(HouseholdLocation::LOCATION_TYPE_RESIDENCE)
            ->setAddress($address);
        if ($household->getHouseholdLocations()) {
            foreach ($household->getHouseholdLocations() as $initialHouseholdLocation) {
                $this->em->remove($initialHouseholdLocation);
            }
        }
        $this->em->flush();
        $household->addHouseholdLocation($householdLocation);

        // Set household location and country specifics
        $country_specific_answers = $this->setCountrySpecificAnswer("KHM", $household, $beneficiary);
        foreach ($country_specific_answers as $country_specific_answer) {
            $household->addCountrySpecificAnswer($country_specific_answer);
        }
        $this->em->persist($household);
    
        return array("household" => $household, "status" => $status);
    }

    /**
     * @param Household $household
     * @param array $beneficiariesInHousehold
     * @return int|void
     * @throws \Exception
     */
    private function insertBeneficiaries(Household $household, array $beneficiariesInHousehold)
    {
        if (!empty($beneficiariesInHousehold)) {
            $hasHead = false;
            $beneficiariesPersisted = [];
            foreach ($beneficiariesInHousehold as $beneficiaryToSave) {
                try {
                    $beneficiary = $this->createBeneficiary($household, $beneficiaryToSave);
                } catch (\Exception $e) {
                    throw $e;
                }
                if ($beneficiary->isHead()) {
                    if ($hasHead) {
                        throw new \Exception("You have defined more than 1 head of household.");
                    }
                    $hasHead = true;
                }
                $this->em->persist($beneficiary);
                $beneficiariesPersisted[] = $beneficiary;
            }
            return count($beneficiariesPersisted);
        }
        return 0;
    }

    /**
     * @param string $countryISO3
     * @param Household $household
     * @param array $beneficiary
     * @return array
     */
    private function setCountrySpecificAnswer(string $countryISO3, Household $household, array $beneficiary)
    {
        $cambodiaCountrySpecifics = ['IDPoor', 'equityCardNo'];
        $countrySpecificAnswers = array();
        
        foreach ($cambodiaCountrySpecifics as $field) {
            $countrySpecific = $this->em->getRepository(CountrySpecific::class)
            ->findOneBy(['fieldString' => $field, 'countryIso3' => $countryISO3]);
            if ($countrySpecific) {
                $countrySpecificAnswerHousehold = $this->em->getRepository(CountrySpecificAnswer::class)->findOneBy(['countrySpecific' => $countrySpecific, 'household' => $household]);

                if (!$countrySpecificAnswerHousehold) {
                    $countrySpecificAnswerHousehold = new CountrySpecificAnswer();
                    $countrySpecificAnswerHousehold->setCountrySpecific($countrySpecific);
                    $countrySpecificAnswerHousehold->setHousehold($household);
                }

                $countrySpecificAnswerHousehold->setAnswer($beneficiary[$field]);

                array_push($countrySpecificAnswers, $countrySpecificAnswerHousehold);
                $this->em->persist($countrySpecificAnswerHousehold);
            }
        }
        return $countrySpecificAnswers;
    }

    /**
     * @param Household $household
     * @param array $beneficiaryArray
     * @return Beneficiary
     */
    private function createBeneficiary(Household $household, array $beneficiaryArray)
    {
        // Check that beneficiary does not already exists
        $beneficiary = $this->em->getRepository(Beneficiary::class)->findOneBy(
            [
                'localGivenName' => $beneficiaryArray["localGivenName"],
                'localFamilyName' => $beneficiaryArray["localFamilyName"],
                'gender' => $beneficiaryArray["gender"],
                'status' => $beneficiaryArray["status"],
                'dateOfBirth' => new DateTime($beneficiaryArray['dateOfBirth'])
            ]
        );

        if (!$beneficiary) {
            $beneficiary = new Beneficiary();
            $beneficiary->setHousehold($household);
            $beneficiary->setGender($beneficiaryArray["gender"])
                        ->setDateOfBirth(new \DateTime($beneficiaryArray["dateOfBirth"])) // From API so no formatting
                        ->setlocalFamilyName($beneficiaryArray["localFamilyName"])
                        ->setlocalGivenName($beneficiaryArray["localGivenName"])
                        ->setStatus($beneficiaryArray["status"])
                        ->setResidencyStatus($beneficiaryArray["residencyStatus"]);
            $profile = new Profile();
        
            /** @var Profile $profile */
            $profile->setPhoto("");
            $beneficiary->setProfile($profile);
            $this->em->persist($profile);
        }

        return $beneficiary;
    }

    /**
     * @param array $village
     * @param string $locationCode
     * @return \CommonBundle\Entity\Location|null
     * @throws \Exception
     */
    private function saveAdm4(array $village, string $locationCode)
    {
        /** @var Adm3 $adm3 */
        $adm3 = $this->em->getRepository(Adm3::class)->findOneBy(['code' => $locationCode]);
        if ($adm3 == null) {
            throw new \Exception("Adm3 was not found.");
        }
        
        $adm4 = $this->em->getRepository(Adm4::class)->findOneBy(['name' => $village['VillageName'], 'adm3' => $adm3]);
        if (!$adm4) {
            $adm4 = new Adm4($adm3);
            $adm4->setName($village['VillageName'])
                ->setCode('KH' . $village['VillageCode']);
            $this->em->persist($adm4);
            $this->em->flush();
        }
        
        return $adm4->getLocation();
    }

    /**
     * Send request to WING API for Cambodia
     * @param  string $type type of the request ("GET", "POST", etc.)
     * @param  string $route url of the request
     * @return mixed  response
     * @throws \Exception
     */
    public function sendRequest(string $type, string $route)
    {
        $curl = curl_init();

        $headers = array();

        array_push($headers, "Authorization: Basic d2ZwOndmcCMxMjM0NQ==");

        curl_setopt_array($curl, array(
            CURLOPT_PORT           => "8383",
            CURLOPT_URL            => $this->url . $route,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $type,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_FAILONERROR    => true,
            CURLINFO_HEADER_OUT    => true
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return array(
                'error' => true,
                'message' => $err
            );
        } else {
            $result = json_decode($response, true);
            return $result;
        }
    }

    /**
     * @return array
     */
    public function getParams()
    {
        $params = array();
        array_push($params, (object) array(
            'paramName' => 'locationCode (KHXXXXXX)',
            'paramType' => 'string'
        ));
        return $params;
    }
}
