<?php

declare(strict_types=1);

namespace Tests\Component\Import;

use Entity\ImportHouseholdDuplicity;
use Entity\ImportQueue;
use Enum\ImportQueueState;
use InputType\Import\Duplicity\ResolveAllDuplicitiesInputType;
use InputType\Import\Duplicity\ResolveSingleDuplicityInputType;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Utils\ProjectService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Entity\Beneficiary;
use Entity\Household;
use Doctrine\ORM\EntityManagerInterface;
use Component\Import\ImportService;
use Component\Import\UploadImportService;
use Entity\Import;
use Entity\ImportFile;
use Enum\ImportState;
use Entity\Project;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Component\Import\Helper\ChecksTrait;
use Tests\Component\Import\Helper\CliTrait;
use Tests\Component\Import\Helper\DefaultDataTrait;

class ImportDuplicityTest extends KernelTestCase
{
    use CliTrait;
    use ChecksTrait;
    use DefaultDataTrait;

    final public const TEST_COUNTRY = 'KHM';

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var Application */
    private $application;

    /** @var ImportService */
    private $importService;

    /** @var UploadImportService */
    private $uploadService;

    private \Entity\Project $project;

    private readonly \Entity\Import $import;

    private \Entity\Household $originHousehold;

    private readonly \Entity\ImportFile $importFile;

    /** @var ProjectService */
    private $projectService;

    private \Symfony\Bundle\FrameworkBundle\KernelBrowser|null $client;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $this->client = self::getContainer()->get('test.client');

        $this->entityManager = self::getContainer()
            ->get('doctrine')
            ->getManager();

        $this->importService = self::getContainer()->get(ImportService::class);

        $this->uploadService = self::getContainer()->get(UploadImportService::class);
        $this->projectService = self::getContainer()->get('project.project_service');

        foreach ($this->entityManager->getRepository(Import::class)->findAll() as $import) {
            $this->entityManager->remove($import);
            foreach ($this->entityManager->getRepository(Beneficiary::class)->getImported($import) as $bnf) {
                if ($bnf->getHousehold()) {
                    self::getContainer()->get('beneficiary.household_service')->remove($bnf->getHousehold());
                }
                self::getContainer()->get('beneficiary.beneficiary_service')->remove($bnf);
            }
        }

        $this->project = $this->createBlankProject(self::TEST_COUNTRY, [__METHOD__]);
        $this->originHousehold = $this->createBlankHousehold($this->project);
    }

    public function testUpdateDuplicities(): void
    {
        $import = $this->makeIdentityCheckFailed(
            $this->project,
            'import_1duplicity_first_run.ods',
            'import_1duplicity_second_run.ods'
        );

        // resolve all as duplicity on second import to update and continue
        $queue = $this->entityManager->getRepository(ImportQueue::class)->findBy(
            ['import' => $import, 'state' => ImportQueueState::IDENTITY_CANDIDATE],
            ['id' => 'asc']
        );
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

            $this->userStartedUploading($import, true);
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
        $this->request('GET', '/api/basic/web-app/v1/imports/' . $import->getId() . '/duplicities');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
    }

    private function request($method, $uri, $body = [], $files = [], $headers = null)
    {
        $headers = array_merge([
            'HTTP_COUNTRY' => 'SYR',
            'PHP_AUTH_USER' => 'admin@example.org',
            'PHP_AUTH_PW' => 'pin1234',
        ], (array) $headers);
        $this->client->request($method, $uri, $body, $files, $headers);
    }
}
