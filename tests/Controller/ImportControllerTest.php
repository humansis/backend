<?php

declare(strict_types=1);

namespace Tests\Controller;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use Component\Import\Finishing\UnexpectedError;
use Controller\ImportController;
use Entity\ImportHouseholdDuplicity;
use Entity\ImportFile;
use Entity\ImportInvalidFile;
use Entity\ImportQueue;
use Enum\ImportQueueState;
use Enum\ImportState;
use Entity\Project;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\BMSServiceTestCase;

class ImportControllerTest extends BMSServiceTestCase
{
    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::getContainer()->get('test.client');
    }

    /**
     * @return int
     * @throws Exception
     */
    public function testCreate(): int
    {
        /** @var Project|null $projects */
        $project = self::getContainer()->get('doctrine')->getRepository(Project::class)->findOneBy([], ['id' => 'asc']);

        if (is_null($project)) {
            $this->markTestSkipped('There needs to be at least one project in system to complete this test');
        }

        $this->request('POST', '/api/basic/web-app/v1/imports', [
            'title' => 'test',
            'description' => 'test',
            'projects' => [$project->getId()],
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $content = $this->client->getResponse()->getContent();
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $content
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('projects', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('createdBy', $result);
        $this->assertArrayHasKey('createdAt', $result);

        $contentDecoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($project->getId(), $contentDecoded['projects'][0]['id']);
        $this->assertEquals($project->getName(), $contentDecoded['projects'][0]['name']);

        $user = $this->getTestUser(self::USER_ADMIN);
        $this->assertEquals($user->getId(), $contentDecoded['createdBy']['id']);
        $this->assertEquals($user->getEmail(), $contentDecoded['createdBy']['email']);

        return $result['id'];
    }

    public function uploadFilesDataProvider(): array
    {
        return [
            ['KHM-Import-2HH-3HHM-24HHM.ods', false],
            ['import_wrong_header.xlsx', true],
            ['import_missing_simple_mandatory_columns.ods', true],
            ['import_missing_address_columns.ods', true],
            ['import_invalid_file.png', true],
        ];
    }

    /**
     * @depends      testCreate
     * @dataProvider uploadFilesDataProvider
     */
    public function testUploadFile(string $filename, bool $expectingViolation)
    {
        $createdProjectId = $this->testCreate();
        $uploadedFilePath = tempnam(sys_get_temp_dir(), 'import');

        $fs = new Filesystem();
        $fs->copy(__DIR__ . '/../Resources/' . $filename, $uploadedFilePath, true);

        $file = new UploadedFile($uploadedFilePath, $filename, null, null, true);

        $this->request('POST', "/api/basic/web-app/v1/imports/$createdProjectId/files", [], [$file]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        // $this->request('GET', "/api/basic/web-app/v1/imports/$id/files");

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        if ($expectingViolation) {
            $this->assertIsArray($result['data'][0]['violations']);
            $this->assertArrayHasKey('columns', $result['data'][0]['violations'][0]);
            $this->assertArrayHasKey('message', $result['data'][0]['violations'][0]);
        } else {
            $this->assertEquals(null, $result['data'][0]['violations']);
        }
    }

    /**
     * @depends testCreate
     *
     *
     * @return int
     */
    public function testGet(int $id)
    {
        $this->request('GET', '/api/basic/web-app/v1/imports/' . $id);

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('projects', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('createdBy', $result);
        $this->assertArrayHasKey('createdAt', $result);

        return $id;
    }

    /**
     * @depends testCreate
     */
    public function testList()
    {
        $this->request('GET', '/api/basic/web-app/v1/imports?page=1&size=10&sort[]=project.desc');

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function patchDataProvider(): array
    {
        return [
            'title change' => [
                'title',
                'New title',
            ],
            'status change' => [
                'status',
                ImportState::CANCELED,
            ],
            'description change' => [
                'description',
                'Lorem ipsum dolor sit amet',
            ],
        ];
    }

    /**
     * @depends      testCreate
     * @dataProvider patchDataProvider
     *
     * @param        $value
     */
    public function testPatch(string $parameter, $value, int $id)
    {
        $this->request(
            'PATCH',
            '/api/basic/web-app/v1/imports/' . $id . '?' . ImportController::DISABLE_CRON . '=true',
            [
                $parameter => $value,
            ]
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->request('GET', '/api/basic/web-app/v1/imports/' . $id);

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($value, $result[$parameter]);
    }

    public function testGetDuplicities()
    {
        /** @var ImportHouseholdDuplicity|null $duplicity */
        $duplicity = $this->em->getRepository(ImportHouseholdDuplicity::class)->findOneBy([], ['id' => 'asc']);

        if (is_null($duplicity)) {
            $this->markTestSkipped('There needs to be at least one import duplicity in system.');
        }

        $importId = $duplicity->getOurs()->getImport()->getId();

        $this->request('GET', "/api/basic/web-app/v1/imports/$importId/duplicities");

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->assertJsonFragment(
            '{
            "totalCount": "*",
            "data": [
                {
                    "id": "*",
                    "itemId": "*",
                    "duplicityCandidateId": "*",
                    "reasons": "*"
                }
            ]}',
            $this->client->getResponse()->getContent()
        );
    }

    public function testGetImportStatistics()
    {
        /** @var ImportQueue|null $importQueue */
        $importQueue = $this->em->getRepository(ImportQueue::class)->findOneBy([], ['id' => 'asc']);

        if (is_null($importQueue)) {
            $this->markTestSkipped('There needs to be at least one import with entries in queue in system.');
        }

        $importId = $importQueue->getImport()->getId();

        $this->request('GET', "/api/basic/web-app/v1/imports/$importId/statistics");

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalEntries', $result);
        $this->assertArrayHasKey('amountIntegrityCorrect', $result);
        $this->assertArrayHasKey('amountIntegrityFailed', $result);
        $this->assertArrayHasKey('amountIdentityDuplicities', $result);
        $this->assertArrayHasKey('amountIdentityDuplicitiesResolved', $result);
        $this->assertArrayHasKey('amountSimilarityDuplicities', $result);
        $this->assertArrayHasKey('amountSimilarityDuplicitiesResolved', $result);
        $this->assertArrayHasKey('amountEntriesToImport', $result);
    }

    public function testGetQueueItem()
    {
        /** @var ImportQueue|null $importQueue */
        $importQueue = $this->em->getRepository(ImportQueue::class)->findOneBy([], ['id' => 'asc']);

        if (is_null($importQueue)) {
            $this->markTestSkipped('There needs to be at least one import import with entries in queue in system.');
        }

        $importQueueId = $importQueue->getId();

        $this->request('GET', "/api/basic/web-app/v1/imports/queue/$importQueueId");

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('values', $result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testResolveDuplicity()
    {
        /** @var ImportHouseholdDuplicity|null $importQueue */
        $duplicity = $this->em->getRepository(ImportHouseholdDuplicity::class)->findOneBy([], ['id' => 'asc']);

        if (is_null($duplicity)) {
            $this->markTestSkipped('There needs to be at least one duplicity with entries in queue in system.');
        }

        $this->request('PATCH', '/api/basic/web-app/v1/imports/queue/' . $duplicity->getOurs()->getId(), [
            'status' => 'To Update',
            'acceptedDuplicityId' => $duplicity->getId(),
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->request('PATCH', '/api/basic/web-app/v1/imports/queue/' . $duplicity->getOurs()->getId(), [
            'status' => 'To Link',
            'acceptedDuplicityId' => $duplicity->getId(),
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->request('PATCH', '/api/basic/web-app/v1/imports/queue/' . $duplicity->getOurs()->getId(), [
            'status' => 'To Update',
            'acceptedDuplicityId' => $duplicity->getId(),
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
    }

    /**
     * @depends testUploadFile
     */
    public function testListValidImportedFiles(): int
    {
        /** @var ImportFile|null $importFile */
        $importFile = $this->em->getRepository(ImportFile::class)->findOneBy([
            'structureViolations' => null,
            'isLoaded' => true,
        ], ['id' => 'asc']);

        if (is_null($importFile)) {
            $this->markTestSkipped('There needs to be at least one import file in system.');
        }

        $importId = $importFile->getImport()->getId();

        $this->request('GET', "/api/basic/web-app/v1/imports/$importId/files");

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->assertJsonFragment(
            '{
            "totalCount": "*",
            "data": [
                {
                    "id": "*",
                    "name": "*",
                    "createdBy": "*",
                    "uploadedDate": "*",
                    "isLoaded": true,
                    "expectedColumns": "*",
                    "missingColumns": "*",
                    "unexpectedColumns": "*",
                    "violations": "*"
                }
            ]}',
            $this->client->getResponse()->getContent()
        );

        return $importFile->getId();
    }

    /**
     *
     * @depends testUploadFile
     * @throws NonUniqueResultException
     */
    public function testListInvalidImportedFiles(): int
    {
        try {
            /** @var ImportFile $importFile */
            $importFile = $this->em->createQueryBuilder()->select('if')
                ->from(ImportFile::class, 'if')
                ->where('if.structureViolations IS NOT NULL and if.isLoaded = true')
                ->setMaxResults(1)
                ->getQuery()->getSingleResult();
        } catch (NoResultException) {
            $this->markTestSkipped('There needs to be at least one import invalid file in system.');
        }

        $importId = $importFile->getImport()->getId();

        $this->request('GET', "/api/basic/web-app/v1/imports/$importId/files");

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->assertJsonFragment(
            '{
            "totalCount": "*",
            "data": [
                {
                    "id": "*",
                    "name": "*",
                    "createdBy": "*",
                    "uploadedDate": "*",
                    "isLoaded": true,
                    "expectedColumns": "*",
                    "missingColumns": "*",
                    "unexpectedColumns": "*",
                    "violations": "*"
                }
            ]}',
            $this->client->getResponse()->getContent()
        );

        return $importFile->getId();
    }

    public function testListInvalidFiles(): int
    {
        /** @var ImportInvalidFile|null $importInvalidFile */
        $importInvalidFile = $this->em->getRepository(ImportInvalidFile::class)->findOneBy([], ['id' => 'asc']);

        if (is_null($importInvalidFile)) {
            $this->markTestSkipped('There needs to be at least one import invalid file in system.');
        }

        $importId = $importInvalidFile->getImport()->getId();

        $this->request('GET', "/api/basic/web-app/v1/imports/$importId/invalid-files");

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->assertJsonFragment(
            '{
            "totalCount": "*",
            "data": [
                {
                    "id": "*",
                    "name": "*",
                    "uploadedDate": "*",
                    "invalidQueueCount": "*"
                }
            ]}',
            $this->client->getResponse()->getContent()
        );

        return $importInvalidFile->getId();
    }

    /**
     * @depends testListInvalidFiles
     */
    public function testGetInvalidFile(int $id)
    {
        $this->request('GET', "/api/basic/web-app/v1/imports/invalid-files/$id");

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
    }

    public function testListQueue()
    {
        /** @var ImportQueue|null $importQueue */
        $importQueue = $this->em->getRepository(ImportQueue::class)->findOneBy([], ['id' => 'asc']);

        if (is_null($importQueue)) {
            $this->markTestSkipped('There needs to be at least one import with items in queue in system.');
        }

        $importId = $importQueue->getImport()->getId();

        $this->request('GET', "/api/basic/web-app/v1/imports/$importId/queue");

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->assertJsonFragment(
            '{
            "totalCount": "*",
            "data": [
                {
                    "id": "*",
                    "values": "*",
                    "status": "*"
                }
            ]}',
            $this->client->getResponse()->getContent()
        );
    }

    public function testListFailedQueue()
    {
        $importQueue = $this->em->getRepository(ImportQueue::class)->findOneBy(
            ['state' => ImportQueueState::ERROR],
            ['id' => 'asc']
        );
        if (!$importQueue) {
            /** @var ImportQueue|null $importQueue */
            $importQueue = $this->em->getRepository(ImportQueue::class)->findOneBy([], ['id' => 'asc']);

            if (is_null($importQueue)) {
                $this->markTestSkipped('There needs to be at least one import with items in queue in system.');
            }

            $importQueue->setUnexpectedError(
                UnexpectedError::create('finishing', new InvalidArgumentException('Some error'))
            );
            $importQueue->setState(ImportQueueState::ERROR);
            $this->em->persist($importQueue);
            $this->em->flush();
            $this->em->clear();
        }

        $importId = $importQueue->getImport()->getId();

        $this->request('GET', "/api/basic/web-app/v1/imports/$importId/fails");

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->assertJsonFragment(
            '{
            "totalCount": "*",
            "data": [
                {
                    "id": "*",
                    "householdId": "*",
                    "beneficiaryId": "*",
                    "failedAction": "*",
                    "errorMessage": "*",
                    "localFamilyName": "*",
                    "localGivenName": "*",
                    "localParentsName": "*",
                    "enFamilyName": "*",
                    "enGivenName": "*",
                    "enParentsName": "*",
                    "primaryIdCard": "*"
                }
            ]}',
            $this->client->getResponse()->getContent()
        );
    }
}
