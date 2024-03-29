<?php

namespace Tests\Utils;

use DateTime;
use Doctrine\Persistence\ObjectManager;
use Entity\Household;
use Entity\HouseholdLocation;
use Enum\ResidencyStatus;
use Utils\HouseholdService;
use Enum\HouseholdAssets;
use Enum\HouseholdShelterStatus;
use Enum\NationalIdType;
use Enum\PersonGender;
use Enum\PhoneTypes;
use InputType\Beneficiary\Address\ResidenceAddressInputType;
use InputType\Beneficiary\BeneficiaryInputType;
use InputType\Beneficiary\NationalIdCardInputType;
use InputType\Beneficiary\PhoneInputType;
use InputType\HouseholdCreateInputType;
use InputType\HouseholdUpdateInputType;
use Entity\Project;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class HouseholdServiceTest extends KernelTestCase
{
    /** @var HouseholdService */
    private $householdService;

    /** @var ObjectManager */
    private $entityManager;

    /** @var ValidatorInterface */
    private $validator;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        // $this->application = new Application($kernel);

        $this->entityManager = self::getContainer()
            ->get('doctrine')
            ->getManager();

        $this->householdService = self::getContainer()->get('beneficiary.household_service');
        $this->validator = self::getContainer()->get('validator');
        // $this->householdService = self::getContainer()->get(HouseholdService::class);
    }

    public function testCreate()
    {
        $createData = new HouseholdCreateInputType();
        $createData->setProjectIds([1]);
        $createData->setAssets(["3", 2]);
        $createData->setLongitude('12.123456');
        $createData->setLatitude('54.321');
        $createData->setNotes('Lorem ipsum');
        $createData->setIncome(3);
        $createData->setCopingStrategiesIndex(3);
        $createData->setFoodConsumptionScore(3);
        $createData->setShelterStatus('3');
        $createData->setDebtLevel(3);
        $createData->setSupportDateReceived(new DateTime('1900-01-01'));
        $createData->setSupportOrganizationName('OSN');
        $createData->setIncomeSpentOnFood(100000);
        $createData->setIncome(3);
        $createData->setEnumeratorName('tester');

        $addressData = new ResidenceAddressInputType();
        $addressData->setLocationId(1);
        $addressData->setNumber(123459);
        $addressData->setPostcode(12345);
        $addressData->setStreet('Fakes st.');
        $createData->setResidenceAddress($addressData);

        $createBeneficiary = new BeneficiaryInputType();
        $createBeneficiary->setGender('M');
        $createBeneficiary->setDateOfBirth('1999-01-01');
        $createBeneficiary->setIsHead(false);
        $createBeneficiary->setLocalGivenName('testGivenMember');
        $createBeneficiary->setLocalFamilyName('testFamilyMember');
        $createBeneficiary->setResidencyStatus(ResidencyStatus::IDP);
        $createData->addBeneficiary($createBeneficiary);

        $createBeneficiary = new BeneficiaryInputType();
        $createBeneficiary->setGender('F');
        $createBeneficiary->setDateOfBirth('2000-12-31');
        $createBeneficiary->setIsHead(true);
        $createBeneficiary->setLocalGivenName('testGiven');
        $createBeneficiary->setLocalFamilyName('testFamily');
        $createBeneficiary->setResidencyStatus(ResidencyStatus::RESIDENT);
        $createData->addBeneficiary($createBeneficiary);

        $phone = new PhoneInputType();
        $phone->setPrefix('+855');
        $phone->setType('Mobile');
        $phone->setProxy(true);
        $phone->setNumber('123 456 789');
        $createBeneficiary->addPhone($phone);

        $phone = new PhoneInputType();
        $phone->setPrefix('+85');
        $phone->setType('Landline');
        $phone->setProxy(false);
        $phone->setNumber('65 5432 14');
        $createBeneficiary->addPhone($phone);

        $nationalId = new NationalIdCardInputType();
        $nationalId->setType(NationalIdType::NATIONAL_ID);
        $nationalId->setNumber('111-222-333');
        $nationalId->setPriority(1);
        $createBeneficiary->addNationalIdCard($nationalId);

        $nationalId = new NationalIdCardInputType();
        $nationalId->setType(NationalIdType::FAMILY);
        $nationalId->setNumber('7897 4657 1234 7896');
        $nationalId->setPriority(2);
        $createBeneficiary->addNationalIdCard($nationalId);

        $violations = $this->validator->validate($createData);
        if ($violations->count() > 0) {
            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                echo "[{$violation->getPropertyPath()} = '{$violation->getInvalidValue()}'] {$violation->getMessage()}\n";
            }
            $this->fail('Testing data are invalid');
        }

        $household = $this->householdService->create($createData, 'ARM');
        $this->entityManager->flush();

        $this->assertNotNull($household);
        $this->assertNotNull($household->getId());
        $this->assertEquals('12.123456', $household->getLongitude());
        $this->assertEquals('54.321', $household->getLatitude());
        $this->assertEquals('Lorem ipsum', $household->getNotes());
        $this->assertCount(2, $household->getAssets());
        $this->assertCount(1, $household->getProjects());
        $this->assertEquals(1, $household->getProjects()[0]->getId());
        $this->assertEquals('KHM', $household->getProjects()[0]->getCountryIso3());
        $this->assertContains(HouseholdAssets::CAR, $household->getAssets());
        $this->assertContains(HouseholdAssets::FLATSCREEN_TV, $household->getAssets());
        $this->assertEquals(3, $household->getIncome());
        $this->assertEquals(3, $household->getCopingStrategiesIndex());
        $this->assertEquals(3, $household->getFoodConsumptionScore());
        $this->assertEquals(HouseholdShelterStatus::TRANSITIONAL_SHELTER, $household->getShelterStatus());
        $this->assertEquals(3, $household->getDebtLevel());
        $this->assertEquals('1900-01-01', $household->getSupportDateReceived()->format('Y-m-d'));
        $this->assertEquals('OSN', $household->getSupportOrganizationName());
        $this->assertEquals(100000, $household->getIncomeSpentOnFood());
        $this->assertEquals(3, $household->getIncome());
        $this->assertEquals('tester', $household->getEnumeratorName());

        $locations = $household->getHouseholdLocations();
        $this->assertCount(1, $locations);
        /** @var HouseholdLocation $location */
        $location = $locations[0];
        $this->assertEquals(HouseholdLocation::LOCATION_TYPE_RESIDENCE, $location->getType());
        $this->assertNotNull($location->getLocation());
        $this->assertEquals(1, $location->getLocation()->getId());
        $this->assertNull($location->getCampAddress());
        $this->assertEquals('123459', $location->getAddress()->getNumber());
        $this->assertEquals('Fakes st.', $location->getAddress()->getStreet());
        $this->assertEquals('12345', $location->getAddress()->getPostcode());

        $head = $household->getHouseholdHead();
        $this->assertNotNull($head, "Missing head");
        $person = $head->getPerson();
        $this->assertEquals('2000-12-31', $person->getDateOfBirth()->format('Y-m-d'));
        $this->assertEquals(PersonGender::FEMALE, $person->getGender());
        $this->assertEquals('testFamily', $person->getLocalFamilyName());
        $this->assertEquals('testGiven', $person->getLocalGivenName());
        $this->assertNull($person->getEnGivenName());
        $this->assertNull($person->getEnFamilyName());
        $this->assertNull($person->getEnParentsName());

        $person = $household->getBeneficiaries()->first()->getPerson();
        $this->assertEquals('1999-01-01', $person->getDateOfBirth()->format('Y-m-d'));
        $this->assertEquals(PersonGender::MALE, $person->getGender());
        $this->assertEquals('testFamilyMember', $person->getLocalFamilyName());
        $this->assertEquals('testGivenMember', $person->getLocalGivenName());
        $this->assertNull($person->getEnGivenName());
        $this->assertNull($person->getEnFamilyName());
        $this->assertNull($person->getEnParentsName());

        $phones = $head->getPerson()->getPhones();
        $this->assertCount(2, $phones, "Wrong phone count");
        $this->assertEquals('+855', $phones[0]->getPrefix());
        $this->assertEquals('123 456 789', $phones[0]->getNumber());
        $this->assertEquals('Mobile', $phones[0]->getType());
        $this->assertTrue($phones[0]->getProxy());

        $this->assertEquals('+85', $phones[1]->getPrefix());
        $this->assertEquals('65 5432 14', $phones[1]->getNumber());
        $this->assertEquals('Landline', $phones[1]->getType());
        $this->assertFalse($phones[1]->getProxy());

        $nationalIds = $head->getPerson()->getNationalIds();
        $this->assertCount(2, $nationalIds, "Wrong nationalID count");
        $this->assertEquals(NationalIdType::NATIONAL_ID, $nationalIds[0]->getIdType());
        $this->assertEquals('111-222-333', $nationalIds[0]->getIdNumber());
        $this->assertEquals(NationalIdType::FAMILY, $nationalIds[1]->getIdType());
        $this->assertEquals('7897 4657 1234 7896', $nationalIds[1]->getIdNumber());

        return $household->getId();
    }

    /**
     * @depends testCreate
     * @param $householdId
     */
    public function testUpdate($householdId)
    {
        $countryCode = 'ARM';
        $updateData = new HouseholdUpdateInputType();
        $updateData->setProjectIds([1, 2]);
        $updateData->setAssets(["1", "3", 5]);
        $updateData->setLongitude('1.000');
        $updateData->setLatitude('2.000');
        $updateData->setNotes('Lorem ipsum set dolor');
        $updateData->setShelterStatus(2);

        $addressData = new ResidenceAddressInputType();
        $addressData->setLocationId(1);
        $addressData->setNumber("000");
        $addressData->setPostcode("000");
        $addressData->setStreet('No street');
        $updateData->setResidenceAddress($addressData);

        $head = new BeneficiaryInputType();
        $head->setGender('F');
        $head->setDateOfBirth('2000-01-01');
        $head->setIsHead(true);
        $head->setLocalGivenName('testGiven');
        $head->setLocalFamilyName('testFamily');
        $head->setResidencyStatus(ResidencyStatus::RESIDENT);
        $updateData->addBeneficiary($head);

        $member = new BeneficiaryInputType();
        $member->setGender('M');
        $member->setDateOfBirth('2000-01-01');
        $member->setIsHead(false);
        $member->setLocalGivenName('000Head');
        $member->setLocalFamilyName('000Head');
        $member->setEnGivenName('000Head');
        $member->setEnFamilyName('000Head');
        $member->setEnParentsName('000Head');
        $member->setResidencyStatus(ResidencyStatus::RETURNEE);
        $updateData->addBeneficiary($member);

        $phone = new PhoneInputType();
        $phone->setPrefix('111');
        $phone->setType(PhoneTypes::LANDLINE);
        $phone->setProxy(false);
        $phone->setNumber('111');
        $head->addPhone($phone);

        $phone = new PhoneInputType();
        $phone->setPrefix('222');
        $phone->setType(PhoneTypes::MOBILE);
        $phone->setProxy(true);
        $phone->setNumber('222');
        $head->addPhone($phone);

        $nationalId = new NationalIdCardInputType();
        $nationalId->setType(NationalIdType::CAMP_ID);
        $nationalId->setNumber('000');
        $head->addNationalIdCard($nationalId);

        $nationalId = new NationalIdCardInputType();
        $nationalId->setType(NationalIdType::BIRTH_CERTIFICATE);
        $nationalId->setNumber('111');
        $head->addNationalIdCard($nationalId);

        $violations = $this->validator->validate($updateData);
        if ($violations->count() > 0) {
            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                echo "[{$violation->getPropertyPath()} = '{$violation->getInvalidValue()}'] {$violation->getMessage()}\n";
            }
            $this->fail('Testing data are wrong');
        }

        $household = $this->entityManager->getRepository(Household::class)->find($householdId);

        $this->validator->validate($updateData);
        $household = $this->householdService->update($household, $updateData, $countryCode);
        $this->entityManager->flush();

        $this->assertNotNull($household);
        $this->assertNotNull($household->getId());
        $this->assertEquals('1.000', $household->getLongitude());
        $this->assertEquals('2.000', $household->getLatitude());
        $this->assertEquals('Lorem ipsum set dolor', $household->getNotes());
        $this->assertCount(3, $household->getAssets());
        $this->assertCount(2, $household->getProjects());
        $this->assertEquals(1, $household->getProjects()[0]->getId());
        $this->assertEquals('KHM', $household->getProjects()[0]->getCountryIso3());
        $this->assertContains(HouseholdAssets::AGRICULTURAL_LAND, $household->getAssets());
        $this->assertContains(HouseholdAssets::FLATSCREEN_TV, $household->getAssets());
        $this->assertContains(HouseholdAssets::MOTORBIKE, $household->getAssets());

        $this->assertCount(2, $household->getBeneficiaries(), "Wrong beneficiary count");
        $head = $household->getHouseholdHead();
        $this->assertNotNull($head);
        $person = $head->getPerson();
        $this->assertEquals('2000-01-01', $person->getDateOfBirth()->format('Y-m-d'));
        $this->assertEquals(PersonGender::FEMALE, $person->getGender());
        $this->assertEquals('testFamily', $person->getLocalFamilyName());
        $this->assertEquals('testGiven', $person->getLocalGivenName());
        $this->assertNull($person->getEnGivenName());
        $this->assertNull($person->getEnFamilyName());
        $this->assertNull($person->getEnParentsName());

        $person = $household->getBeneficiaries()->last()->getPerson();
        $this->assertEquals('2000-01-01', $person->getDateOfBirth()->format('Y-m-d'));
        $this->assertEquals(PersonGender::MALE, $person->getGender());
        $this->assertEquals('000Head', $person->getLocalFamilyName());
        $this->assertEquals('000Head', $person->getLocalGivenName());
        $this->assertEquals('000Head', $person->getEnGivenName());
        $this->assertEquals('000Head', $person->getEnFamilyName());
        $this->assertEquals('000Head', $person->getEnParentsName());

        $phones = $head->getPerson()->getPhones();
        $this->assertCount(2, $phones, "Wrong phone count");
        $this->assertEquals('111', $phones[0]->getPrefix());
        $this->assertEquals('111', $phones[0]->getNumber());
        $this->assertEquals(PhoneTypes::LANDLINE, $phones[0]->getType());
        $this->assertFalse($phones[0]->getProxy());

        $this->assertEquals('222', $phones[1]->getPrefix());
        $this->assertEquals('222', $phones[1]->getNumber());
        $this->assertEquals(PhoneTypes::MOBILE, $phones[1]->getType());
        $this->assertTrue($phones[1]->getProxy());

        $nationalIds = $head->getPerson()->getNationalIds();
        $this->assertCount(2, $nationalIds, "Wrong nationalID count");
        $this->assertEquals(NationalIdType::CAMP_ID, $nationalIds[0]->getIdType());
        $this->assertEquals('000', $nationalIds[0]->getIdNumber());
        $this->assertEquals(NationalIdType::BIRTH_CERTIFICATE, $nationalIds[1]->getIdType());
        $this->assertEquals('111', $nationalIds[1]->getIdNumber());
    }

    public function testUpdateBeneficiaryInHousehold()
    {
        /** @var Project|null $project */
        $project = $this->entityManager->getRepository(Project::class)->findOneBy([], ['id' => 'asc']);

        if (is_null($project)) {
            $this->markTestSkipped('There needs to be at least one project in system to complete this test');
        }

        $householdCreateInputType = new HouseholdCreateInputType();
        $householdCreateInputType->setProjectIds([$project->getId()]);
        $householdCreateInputType->setAssets([HouseholdAssets::valueToAPI(HouseholdAssets::MOTORBIKE)]);
        $householdCreateInputType->setShelterStatus(3);

        $addressData = new ResidenceAddressInputType();
        $addressData->setLocationId(1);
        $addressData->setNumber(123459);
        $addressData->setPostcode(12345);
        $addressData->setStreet('Fakes st.');
        $householdCreateInputType->setResidenceAddress($addressData);

        $beneficiaryInputType = new BeneficiaryInputType();
        $beneficiaryInputType->setGender('M');
        $beneficiaryInputType->setDateOfBirth('2000-01-01');
        $beneficiaryInputType->setIsHead(true);
        $beneficiaryInputType->setLocalGivenName('000Head');
        $beneficiaryInputType->setLocalFamilyName('000Head');
        $beneficiaryInputType->setEnGivenName('000Head');
        $beneficiaryInputType->setEnFamilyName('000Head');
        $beneficiaryInputType->setEnParentsName('000Head');
        $beneficiaryInputType->setResidencyStatus(ResidencyStatus::RETURNEE);
        $householdCreateInputType->addBeneficiary($beneficiaryInputType);

        $household = $this->householdService->create($householdCreateInputType, 'ARM');
        $this->entityManager->flush();

        $this->assertEquals(ResidencyStatus::RETURNEE, $household->getBeneficiaries()->first()->getResidencyStatus());

        $householdUpdateInputType = new HouseholdUpdateInputType();
        $householdUpdateInputType->setProjectIds([$project->getId()]);
        $householdUpdateInputType->setAssets([HouseholdAssets::valueToAPI(HouseholdAssets::LIVESTOCK)]);
        $householdCreateInputType->setShelterStatus(2);

        $beneficiaryInputType->setResidencyStatus(ResidencyStatus::IDP);
        $householdUpdateInputType->addBeneficiary($beneficiaryInputType);

        $householdUpdateInputType->setResidenceAddress($addressData);

        $this->householdService->update($household, $householdUpdateInputType, 'ARM');
        $this->entityManager->flush();
        $this->entityManager->refresh($household);

        $this->assertEquals(1, $household->getBeneficiaries()->count());
        $this->assertEquals(ResidencyStatus::IDP, $household->getBeneficiaries()->first()->getResidencyStatus());
    }
}
