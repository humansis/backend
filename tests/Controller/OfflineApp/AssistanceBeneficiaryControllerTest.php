<?php

declare(strict_types=1);

namespace Tests\Controller\OfflineApp;

use Entity\Beneficiary;
use Entity\Community;
use Entity\Institution;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Enum\AssistanceTargetType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Exception;
use Tests\BMSServiceTestCase;

class AssistanceBeneficiaryControllerTest extends BMSServiceTestCase
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

    public function testGetAssistanceBeneficiariesByAssistance()
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();

        try {
            $assistanceId = $em->createQueryBuilder()
                ->select('a.id')
                ->from(Beneficiary::class, 'b')
                ->join('b.assistanceBeneficiary', 'ab')
                ->join('ab.assistance', 'a')
                ->where('b.archived = 0')
                ->andWhere('a.archived = 0')
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (NoResultException) {
            $this->markTestSkipped(
                'You need to have at least one assistance with beneficiary in database to complete this test.'
            );
        }

        $this->request('GET', '/api/basic/offline-app/v2/assistances/' . $assistanceId . '/assistances-beneficiaries');

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
                    "beneficiaryId": "*",
                    "removed": "*",
                    "justification": "*",
                    "lastSmartcardDepositId": "*"
                }
            ]}',
            $this->client->getResponse()->getContent()
        );
    }

    public function testGetAssistanceInstitutionsByAssistance()
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();

        try {
            $assistanceId = $em->createQueryBuilder()
                ->select('a.id')
                ->from(Institution::class, 'i')
                ->join('i.assistanceBeneficiary', 'ab')
                ->join('ab.assistance', 'a')
                ->andWhere('a.archived = 0')
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (NoResultException) {
            $this->markTestSkipped(
                'You need to have at least one assistance with institution in database to complete this test.'
            );
        }

        $this->request(
            'GET',
            '/api/basic/offline-app/v1/assistances/' . $assistanceId . '/assistances-institutions?sort[]=id.desc'
        );

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
                    "institutionId": "*",
                    "removed": "*",
                    "justification": "*",
                    "reliefPackageIds": "*"
                }
            ]}',
            $this->client->getResponse()->getContent()
        );
    }

    public function testGetAssistanceCommunitiesByAssistance()
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();

        try {
            $assistanceId = $em->createQueryBuilder()
                ->select('a.id')
                ->from(Community::class, 'c')
                ->join('c.assistanceBeneficiary', 'ab')
                ->join('ab.assistance', 'a')
                ->andWhere('a.archived = 0')
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (NoResultException) {
            $this->markTestSkipped(
                'You need to have at least one assistance with community in database to complete this test.'
            );
        }

        $this->request(
            'GET',
            '/api/basic/offline-app/v1/assistances/' . $assistanceId . '/assistances-communities?sort[]=id.desc'
        );

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
                    "communityId": "*",
                    "removed": "*",
                    "justification": "*",
                    "reliefPackageIds": "*"
                }
            ]}',
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testAddBeneficiaryToAssistanceByIds()
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();
        $assistance = $em->getRepository(Assistance::class)->findOneBy([
            'validatedBy' => null,
            'completed' => false,
            'archived' => false,
            'targetType' => AssistanceTargetType::INDIVIDUAL,
        ], ['id' => 'asc']);
        $beneficiary = $em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'desc']);
        $target = $em->getRepository(AssistanceBeneficiary::class)->findOneBy([
            'beneficiary' => $beneficiary,
            'assistance' => $assistance,
        ], ['id' => 'asc']);
        if ($target) {
            $em->remove($target);
            $em->flush();
        }

        $this->request(
            'PUT',
            '/api/basic/web-app/v1/assistances/' . $assistance->getId() . '/assistances-beneficiaries',
            [
                'beneficiaryIds' => [$beneficiary->getId()],
                'justification' => 'test',
            ]
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        return [$assistance->getId(), $beneficiary->getId()];
    }

    /**
     * @depends testAddBeneficiaryToAssistanceByIds
     */
    public function testRemoveBeneficiaryFromAssistanceByIds(array $data)
    {
        [$assistanceId, $beneficiaryId] = $data;

        $this->request('DELETE', '/api/basic/web-app/v1/assistances/' . $assistanceId . '/assistances-beneficiaries', [
            'beneficiaryIds' => [$beneficiaryId],
            'justification' => 'test',
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->request(
            'GET',
            '/api/basic/offline-app/v3/assistances/' . $assistanceId . '/targets/beneficiaries?sort[]=id.desc'
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        foreach ($result['data'] as $data) {
            $this->assertNotEquals($beneficiaryId, $data['beneficiary']['id'], "Target $beneficiaryId wasn't removed");
        }
    }

    /**
     * @throws Exception
     */
    public function testAddInstitutionToAssistance()
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();
        $assistance = $em->getRepository(Assistance::class)->findOneBy([
            'validatedBy' => null,
            'completed' => false,
            'archived' => false,
            'targetType' => AssistanceTargetType::INSTITUTION,
        ], ['id' => 'asc']);
        $institution = $em->getRepository(Institution::class)->findOneBy([], ['id' => 'desc']);

        $this->request(
            'PUT',
            '/api/basic/web-app/v1/assistances/' . $assistance->getId() . '/assistances-institutions',
            [
                'institutionIds' => [$institution->getId()],
                'justification' => 'test',
                'added' => true,
            ]
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        return [$assistance->getId(), $institution->getId()];
    }

    /**
     * @depends testAddInstitutionToAssistance
     */
    public function testRemoveInstitutionFromAssistance($data)
    {
        [$assistanceId, $institutionId] = $data;

        $this->request('PUT', '/api/basic/web-app/v1/assistances/' . $assistanceId . '/assistances-institutions', [
            'institutionIds' => [$institutionId],
            'justification' => 'test',
            'removed' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->request(
            'GET',
            '/api/basic/offline-app/v1/assistances/' . $assistanceId . '/assistances-institutions?sort[]=id.desc'
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        foreach ($result['data'] as $data) {
            $this->assertNotEquals($institutionId, $data['institutionId'], "Target $institutionId wasn't removed");
        }
    }

    /**
     * @throws Exception
     */
    public function testAddCommunityToAssistance()
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();
        $assistance = $em->getRepository(Assistance::class)->findOneBy([
            'validatedBy' => null,
            'completed' => false,
            'archived' => false,
            'targetType' => AssistanceTargetType::COMMUNITY,
        ], ['id' => 'asc']);
        $community = $em->getRepository(Community::class)->findOneBy([], ['id' => 'desc']);

        $this->request(
            'PUT',
            '/api/basic/web-app/v1/assistances/' . $assistance->getId() . '/assistances-communities',
            [
                'communityIds' => [$community->getId()],
                'justification' => 'test',
                'added' => true,
            ]
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        return [$assistance->getId(), $community->getId()];
    }

    /**
     * @depends testAddCommunityToAssistance
     */
    public function testRemoveCommunityFromAssistance($data)
    {
        [$assistanceId, $communityId] = $data;

        $this->request('PUT', '/api/basic/web-app/v1/assistances/' . $assistanceId . '/assistances-communities', [
            'communityIds' => [$communityId],
            'justification' => 'test',
            'removed' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->request(
            'GET',
            '/api/basic/offline-app/v1/assistances/' . $assistanceId . '/assistances-communities?sort[]=id.desc'
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        foreach ($result['data'] as $data) {
            $this->assertNotEquals($communityId, $data['communityId'], "Target $communityId wasn't removed");
        }
    }
}
