<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Import;

use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Repository\BeneficiaryRepository;
use NewApiBundle\Component\Import\ImportFileValidator;
use NewApiBundle\Component\Import\Integrity\DuplicityService;
use NewApiBundle\Entity\ImportHouseholdDuplicity;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\HouseholdAssets;
use NewApiBundle\Enum\HouseholdShelterStatus;
use NewApiBundle\Enum\HouseholdSupportReceivedType;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\NationalIdType;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\InputType\Import\Duplicity\ResolveAllDuplicitiesInputType;
use NewApiBundle\InputType\Import\Duplicity\ResolveSingleDuplicityInputType;
use ProjectBundle\Enum\Livelihood;
use ProjectBundle\Utils\ProjectService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\ImportService;
use NewApiBundle\Component\Import\UploadImportService;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportFile;
use NewApiBundle\Enum\ImportState;
use ProjectBundle\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\NewApiBundle\Component\Import\Helper\ChecksTrait;
use Tests\NewApiBundle\Component\Import\Helper\CliTrait;
use Tests\NewApiBundle\Component\Import\Helper\DefaultDataTrait;

class ImportDuplicityTest extends KernelTestCase
{
    use CliTrait;
    use ChecksTrait;
    use DefaultDataTrait;

    const TEST_COUNTRY = 'KHM';

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var Application */
    private $application;

    /** @var ImportService */
    private $importService;

    /** @var UploadImportService */
    private $uploadService;

    /** @var Project */
    private $project;

    /** @var Import */
    private $import;

    /** @var Household */
    private $originHousehold;

    /** @var ImportFile */
    private $importFile;
    /** @var ProjectService */
    private $projectService;

    /**
     * @var object|\Symfony\Bundle\FrameworkBundle\KernelBrowser|null
     */
    private $client;

    protected function setUp()
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $this->client = $kernel->getContainer()->get('test.client');

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->importService = $kernel->getContainer()->get(ImportService::class);

        $this->uploadService = $kernel->getContainer()->get(UploadImportService::class);
        $this->projectService = $kernel->getContainer()->get('project.project_service');

        foreach ($this->entityManager->getRepository(Import::class)->findAll() as $import) {
            $this->entityManager->remove($import);
            foreach ($this->entityManager->getRepository(Beneficiary::class)->getImported($import) as $bnf) {
                if ($bnf->getHousehold()) {
                    $kernel->getContainer()->get('beneficiary.household_service')->remove($bnf->getHousehold());
                }
                $kernel->getContainer()->get('beneficiary.beneficiary_service')->remove($bnf);
            }
        }

        $this->project = $this->createBlankProject(self::TEST_COUNTRY, [__METHOD__]);
        $this->originHousehold = $this->createBlankHousehold($this->project);
    }

    public function testUpdateDuplicities()
    {
        $import = $this->makeIdentityCheckFailed(
            $this->project,
            'import_1duplicity_first_run.ods',
            'import_1duplicity_second_run.ods'
        );

        // resolve all as duplicity on second import to update and continue
        $queue = $this->entityManager->getRepository(ImportQueue::class)->findBy(['import' => $import, 'state' => ImportQueueState::IDENTITY_CANDIDATE], ['id' => 'asc']);
        $this->assertQueueCount(2, $import);
        $this->assertQueueCount(2, $import, [ImportQueueState::IDENTITY_CANDIDATE]);
        $this->checkDuplicityEndpoint($import);

        /** @var ImportQueue $item */
        foreach ($queue as $item) {
            $this->assertGreaterThan(0, count($item->getHouseholdDuplicities()));
            /** @var ImportHouseholdDuplicity $firstDuplicity */
            $firstDuplicity = $item->getHouseholdDuplicities()->first();

            $duplicityResolve = new ResolveSingleDuplicityInputType();
            $duplicityResolve->setStatus(ImportQueueState::TO_UPDATE);
            $duplicityResolve->setAcceptedDuplicityId($firstDuplicity->getTheirs()->getId());
            $this->importService->resolveDuplicity($item, $duplicityResolve, $this->getUser());
        }
        $this->assertQueueCount(2, $import);
        $this->assertQueueCount(2, $import, [ImportQueueState::TO_UPDATE]);
        $this->assertEquals(ImportState::IDENTITY_CHECK_CORRECT, $import->getState());
        $this->checkDuplicityEndpoint($import);
    }

    public function testUpdateDuplicitiesByBatch()
    {
        $import = $this->makeIdentityCheckFailed(
            $this->project,
            'import_1duplicity_first_run.ods',
            'import_1duplicity_second_run.ods'
        );
        $this->assertQueueCount(2, $import);
        $this->assertQueueCount(2, $import, [ImportQueueState::IDENTITY_CANDIDATE]);
        $this->checkDuplicityEndpoint($import);

        $duplicityResolve = new ResolveAllDuplicitiesInputType();
        $duplicityResolve->setStatus(ImportQueueState::TO_UPDATE);
        $this->importService->resolveAllDuplicities($import, $duplicityResolve, $this->getUser());

        $this->assertQueueCount(2, $import);
        $this->assertQueueCount(2, $import, [ImportQueueState::TO_UPDATE]);
        $this->checkDuplicityEndpoint($import);

        $this->assertEquals(ImportState::IDENTITY_CHECK_CORRECT, $import->getState());
    }

    private function makeIdentityCheckFailed(Project $project, string $firstFile, string $secondFile): Import
    {
        $testFiles = [
            'first' => $firstFile,
            'second' => $secondFile,
        ];

        $imports = [];
        foreach (['first', 'second'] as $runName) {
            $import = $this->createImport("testUpdateSimpleDuplicity[$runName]", $project, $testFiles[$runName]);

            $this->userStartedIntegrityCheck($import, true);
            $this->userStartedIdentityCheck($import, true);
            $this->userStartedSimilarityCheck($import, true);

            $imports[$runName] = $import;
        }

        // finish first
        $firstImport = $imports['first'];
        $secondImport = $imports['second'];

        $this->userStartedFinishing($firstImport);

        $this->entityManager->refresh($firstImport);
        $this->entityManager->refresh($secondImport);

        return $secondImport;
    }

    private function checkDuplicityEndpoint(Import $import)
    {
        $this->request('GET', '/api/basic/web-app/v1/imports/'.$import->getId().'/duplicities');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
    }

    private function request($method, $uri, $body = [], $files = [], $headers = null)
    {
        $headers = array_merge([
            'HTTP_COUNTRY' => 'SYR',
            'PHP_AUTH_USER' => 'admin@example.org',
            'PHP_AUTH_PW'   => 'pin1234'
        ], (array) $headers);
        $this->client->request($method, $uri, $body, $files, $headers);
    }

}
