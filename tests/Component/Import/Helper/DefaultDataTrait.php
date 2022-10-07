<?php

declare(strict_types=1);

namespace Tests\Component\Import\Helper;

use DateTime;
use Entity\Address;
use Entity\Beneficiary;
use Entity\Household;
use Entity\HouseholdLocation;
use Entity\NationalId;
use Entity;
use Enum\ImportState;
use Enum\NationalIdType;
use Enum\PersonGender;
use InputType\Import;
use Entity\Project;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Entity\User;

trait DefaultDataTrait
{
    private function createBlankHousehold(Project $project): Household
    {
        $hh = new Household();

        $hh->setLongitude('empty');
        $hh->setLatitude('empty');
        $hh->setCopingStrategiesIndex(0);
        $hh->setDebtLevel(0);
        $hh->setFoodConsumptionScore(0);
        $hh->setIncome(0);
        $hh->setNotes('default HH in ' . __CLASS__);
        $hh->setCountryIso3($project->getCountryIso3());

        $householdLocation = new HouseholdLocation();
        $householdLocation->setLocationGroup(HouseholdLocation::LOCATION_GROUP_CURRENT);
        $householdLocation->setType(HouseholdLocation::LOCATION_TYPE_RESIDENCE);

        $location = $this->entityManager->getRepository(Entity\Location::class)->findOneBy([
            'countryIso3' => $project->getCountryIso3(),
        ]);

        if ($location === null) {
            throw new RuntimeException(
                "Cannot create household. There is no location in {$project->getCountryIso3()}"
            );
        }

        $householdLocation->setAddress(
            Address::create(
                'Fake st',
                '1234',
                '420 00',
                $location
            )
        );

        $hh->addHouseholdLocation($householdLocation);

        $hhh = new Beneficiary();
        $hhh->setHousehold($hh);
        $birthDate = new DateTime();
        $birthDate->modify("-30 year");
        $hhh->getPerson()->setDateOfBirth($birthDate);
        $hhh->getPerson()->setEnFamilyName('empty');
        $hhh->getPerson()->setEnGivenName('empty');
        $hhh->getPerson()->setLocalFamilyName('empty');
        $hhh->getPerson()->setLocalGivenName('empty');
        $hhh->getPerson()->setGender(PersonGender::FEMALE);
        $hhh->setHead(true);
        $hhh->setResidencyStatus('empty');

        $nationalId = new NationalId();
        $nationalId->setIdType(NationalIdType::NATIONAL_ID);
        $nationalId->setIdNumber('123456789');
        $hhh->getPerson()->addNationalId($nationalId);
        $nationalId->setPerson($hhh->getPerson());

        $hh->addBeneficiary($hhh);
        $hh->addProject($project);
        $hhh->addProject($project);
        $this->entityManager->persist($nationalId);
        $this->entityManager->persist($hh);
        $this->entityManager->persist($hhh);
        $this->entityManager->flush();

        return $hh;
    }

    private function getUser(): User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy([], ['id' => 'asc']);
    }

    private function createImport(string $name, Project $project, ?string $fileName = null): Entity\Import
    {
        $createImportInput = new Import\CreateInputType();
        $createImportInput->setTitle($name);
        $createImportInput->setDescription(__METHOD__);
        $createImportInput->setProjects([$project->getId()]);
        $import = $this->importService->create($project->getCountryIso3(), $createImportInput, $this->getUser());

        $this->assertNotNull($import->getId(), "Import wasn't saved to DB");
        $this->assertEquals(ImportState::NEW, $import->getState());

        if ($fileName) {
            $this->uploadFile($import, $fileName);
        }

        return $import;
    }

    private function createBlankProject(string $country, array $notes): Project
    {
        $project = new Project();
        $project->setName(uniqid());
        $project->setNotes(implode("\n", $notes));
        $project->setStartDate(new DateTime());
        $project->setEndDate(new DateTime());
        $project->setCountryIso3($country);
        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }

    private function uploadFile(Entity\Import $import, string $filename): void
    {
        $uploadedFilePath = tempnam(sys_get_temp_dir(), 'import');

        $fs = new Filesystem();
        $fs->copy(__DIR__ . '/../../../Resources/' . $filename, $uploadedFilePath, true);

        $file = new UploadedFile($uploadedFilePath, $filename, null, null, true);
        $this->uploadService->uploadFile($import, $file, $this->getUser());
    }
}
