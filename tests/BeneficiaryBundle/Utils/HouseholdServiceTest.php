<?php

namespace Tests\BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Enum\ResidencyStatus;
use BeneficiaryBundle\Utils\HouseholdService;
use Doctrine\Common\Persistence\ObjectManager;
use NewApiBundle\Component\Import\ImportService;
use NewApiBundle\InputType\Beneficiary\Address\ResidenceAddressInputType;
use NewApiBundle\InputType\Beneficiary\AddressInputType;
use NewApiBundle\InputType\Beneficiary\BeneficiaryInputType;
use NewApiBundle\InputType\Beneficiary\NationalIdCardInputType;
use NewApiBundle\InputType\Beneficiary\PhoneInputType;
use NewApiBundle\InputType\HouseholdCreateInputType;
use NewApiBundle\InputType\HouseholdUpdateInputType;
use Symfony\Bundle\FrameworkBundle\Console\Application;
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

    protected function setUp()
    {
        $kernel = self::bootKernel();
        // $this->application = new Application($kernel);

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->householdService = $kernel->getContainer()->get('beneficiary.household_service');
        $this->validator = $kernel->getContainer()->get('validator');
        // $this->householdService = $kernel->getContainer()->get(HouseholdService::class);
    }

    public function testCreate()
    {
        $createData = new HouseholdCreateInputType();
        $createData->setProjectIds([1]);
        $createData->setIso3('KHM');
        $createData->setAssets(["3", 2]);
        $createData->setLongitude('12.123456');
        $createData->setLatitude('54.321');
        $createData->setNotes('Lorem ipsum');
        $createData->setIncomeLevel(3);
        $createData->setCopingStrategiesIndex(3);
        $createData->setFoodConsumptionScore(3);
        $createData->setShelterStatus(3);
        $createData->setDebtLevel(3);
        $createData->setSupportDateReceived('1900-01-01');
        $createData->setSupportOrganizationName('OSN');
        $createData->setIncomeSpentOnFood(100000);
        $createData->setIncomeLevel(3);
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
        $nationalId->setType(NationalId::TYPE_NATIONAL_ID);
        $nationalId->setNumber('111-222-333');
        $createBeneficiary->addNationalIdCard($nationalId);

        $nationalId = new NationalIdCardInputType();
        $nationalId->setType(NationalId::TYPE_FAMILY);
        $nationalId->setNumber('7897 4657 1234 7896');
        $createBeneficiary->addNationalIdCard($nationalId);

        $violations = $this->validator->validate($createData);
        if ($violations->count() > 0) {
            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                echo "[{$violation->getPropertyPath()} = '{$violation->getInvalidValue()}'] {$violation->getMessage()}\n";
            }
            $this->fail('Testing data are invalid');
        }

        $household = $this->householdService->create($createData);
        $this->assertNotNull($household);
        $this->assertNotNull($household->getId());
        $this->assertEquals('12.123456', $household->getLongitude());
        $this->assertEquals('54.321', $household->getLatitude());
        $this->assertEquals('Lorem ipsum', $household->getNotes());
        $this->assertCount(2, $household->getAssets());
        $this->assertCount(1, $household->getProjects());
        $this->assertEquals(1, $household->getProjects()[0]->getId());
        $this->assertEquals('KHM', $household->getProjects()[0]->getIso3());
        $this->assertContains(2, $household->getAssets());
        $this->assertContains(3, $household->getAssets());
        $this->assertEquals(3, $household->getIncomeLevel());
        $this->assertEquals(3, $household->getCopingStrategiesIndex());
        $this->assertEquals(3, $household->getFoodConsumptionScore());
        $this->assertEquals(3, $household->getShelterStatus());
        $this->assertEquals(3, $household->getDebtLevel());
        $this->assertEquals('1900-01-01', $household->getSupportDateReceived()->format('Y-m-d'));
        $this->assertEquals('OSN', $household->getSupportOrganizationName());
        $this->assertEquals(100000, $household->getIncomeSpentOnFood());
        $this->assertEquals(3, $household->getIncomeLevel());
        $this->assertEquals('tester', $household->getEnumeratorName());

        $head = $household->getHouseholdHead();
        $person = $head->getPerson();
        $this->assertEquals('2000-12-31', $person->getDateOfBirth()->format('Y-m-d'));
        $this->assertEquals(0, $person->getGender());
        $this->assertEquals('testFamily', $person->getLocalFamilyName());
        $this->assertEquals('testGiven', $person->getLocalGivenName());
        $this->assertNull($person->getEnGivenName());
        $this->assertNull($person->getEnFamilyName());
        $this->assertNull($person->getEnParentsName());

        $person = $household->getBeneficiaries()->first()->getPerson();
        $this->assertEquals('1999-01-01', $person->getDateOfBirth()->format('Y-m-d'));
        $this->assertEquals(1, $person->getGender());
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
        $this->assertEquals(NationalId::TYPE_NATIONAL_ID, $nationalIds[0]->getIdType());
        $this->assertEquals('111-222-333', $nationalIds[0]->getIdNumber());
        $this->assertEquals(NationalId::TYPE_FAMILY, $nationalIds[1]->getIdType());
        $this->assertEquals('7897 4657 1234 7896', $nationalIds[1]->getIdNumber());

        return $household->getId();
    }

    /**
     * @depends testCreate
     * @param $householdId
     */
    public function testUpdate($householdId)
    {
        $updateData = new HouseholdUpdateInputType();
        $updateData->setProjectIds([1,2]);
        $updateData->setIso3('KHM');
        $updateData->setAssets(["1", "3", 5]);
        $updateData->setLongitude('1.000');
        $updateData->setLatitude('2.000');
        $updateData->setNotes('Lorem ipsum set dolor');

        $addressData = new ResidenceAddressInputType();
        $addressData->setLocationId(1);
        $addressData->setNumber("000");
        $addressData->setPostcode("000");
        $addressData->setStreet('No street');
        $updateData->setResidenceAddress($addressData);

        $createBeneficiary = new BeneficiaryInputType();
        $createBeneficiary->setGender('M');
        $createBeneficiary->setDateOfBirth('2000-01-01');
        $createBeneficiary->setIsHead(true);
        $createBeneficiary->setLocalGivenName('000Head');
        $createBeneficiary->setLocalFamilyName('000Head');
        $createBeneficiary->setEnGivenName('000Head');
        $createBeneficiary->setEnFamilyName('000Head');
        $createBeneficiary->setEnParentsName('000Head');
        $createBeneficiary->setResidencyStatus(ResidencyStatus::RETURNEE);
        $updateData->addBeneficiary($createBeneficiary);

        $phone = new PhoneInputType();
        $phone->setPrefix('111');
        $phone->setType('111');
        $phone->setProxy(false);
        $phone->setNumber('111');
        $createBeneficiary->addPhone($phone);

        $phone = new PhoneInputType();
        $phone->setPrefix('222');
        $phone->setType('222');
        $phone->setProxy(true);
        $phone->setNumber('222');
        $createBeneficiary->addPhone($phone);

        $nationalId = new NationalIdCardInputType();
        $nationalId->setType(NationalId::TYPE_CAMP_ID);
        $nationalId->setNumber('000');
        $createBeneficiary->addNationalIdCard($nationalId);

        $nationalId = new NationalIdCardInputType();
        $nationalId->setType(NationalId::TYPE_BIRTH_CERTIFICATE);
        $nationalId->setNumber('111');
        $createBeneficiary->addNationalIdCard($nationalId);

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
        $household = $this->householdService->update($household, $updateData);

        $this->assertNotNull($household);
        $this->assertNotNull($household->getId());
        $this->assertEquals('1.000', $household->getLongitude());
        $this->assertEquals('2.000', $household->getLatitude());
        $this->assertEquals('Lorem ipsum set dolor', $household->getNotes());
        $this->assertCount(3, $household->getAssets());
        $this->assertCount(2, $household->getProjects());
        $this->assertEquals(1, $household->getProjects()[0]->getId());
        $this->assertEquals('KHM', $household->getProjects()[0]->getIso3());
        $this->assertContains(1, $household->getAssets());
        $this->assertContains(3, $household->getAssets());
        $this->assertContains(5, $household->getAssets());

        $head = $household->getHouseholdHead();
        $person = $head->getPerson();
        $this->assertEquals('2000-01-01', $person->getDateOfBirth()->format('Y-m-d'));
        $this->assertEquals(1, $person->getGender());
        $this->assertEquals('000Head', $person->getLocalFamilyName());
        $this->assertEquals('000Head', $person->getLocalGivenName());
        $this->assertEquals('000Head', $person->getEnGivenName());
        $this->assertEquals('000Head', $person->getEnFamilyName());
        $this->assertEquals('000Head', $person->getEnParentsName());

        $this->assertCount(1, $household->getBeneficiaries(), "Wrong beneficiary count");
        $person = $household->getBeneficiaries()->first()->getPerson();
        $this->assertEquals('2000-01-01', $person->getDateOfBirth()->format('Y-m-d'));
        $this->assertEquals(1, $person->getGender());
        $this->assertEquals('000Head', $person->getLocalFamilyName());
        $this->assertEquals('000Head', $person->getLocalGivenName());
        $this->assertEquals('000Head', $person->getEnGivenName());
        $this->assertEquals('000Head', $person->getEnFamilyName());
        $this->assertEquals('000Head', $person->getEnParentsName());

        $phones = $head->getPerson()->getPhones();
        $this->assertCount(2, $phones, "Wrong phone count");
        $this->assertEquals('111', $phones[0]->getPrefix());
        $this->assertEquals('111', $phones[0]->getNumber());
        $this->assertEquals('111', $phones[0]->getType());
        $this->assertFalse($phones[0]->getProxy());

        $this->assertEquals('222', $phones[1]->getPrefix());
        $this->assertEquals('222', $phones[1]->getNumber());
        $this->assertEquals('222', $phones[1]->getType());
        $this->assertTrue($phones[1]->getProxy());

        $nationalIds = $head->getPerson()->getNationalIds();
        $this->assertCount(2, $nationalIds, "Wrong nationalID count");
        $this->assertEquals(NationalId::TYPE_CAMP_ID, $nationalIds[0]->getIdType());
        $this->assertEquals('000', $nationalIds[0]->getIdNumber());
        $this->assertEquals(NationalId::TYPE_BIRTH_CERTIFICATE, $nationalIds[1]->getIdType());
        $this->assertEquals('111', $nationalIds[1]->getIdNumber());
    }

}
