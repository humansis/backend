<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Import\Helper;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use NewApiBundle\Entity;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Enum\NationalIdType;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\InputType\Import;
use ProjectBundle\Entity\Project;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use UserBundle\Entity\User;

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
        $hh->setNotes('default HH in '.__CLASS__);

        $hhh = new Beneficiary();
        $hhh->setHousehold($hh);
        $birthDate = new \DateTime();
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
        $project->setStartDate(new \DateTime());
        $project->setEndDate(new \DateTime());
        $project->setCountryIso3($country);
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        return $project;
    }

    private function uploadFile(Entity\Import $import, string $filename): void
    {
        $uploadedFilePath = tempnam(sys_get_temp_dir(), 'import');

        $fs = new Filesystem();
        $fs->copy(__DIR__.'/../../../Resources/'.$filename, $uploadedFilePath, true);

        $file = new UploadedFile($uploadedFilePath, $filename, null, null, true);
        $this->uploadService->uploadFile($import, $file, $this->getUser());
    }

}
