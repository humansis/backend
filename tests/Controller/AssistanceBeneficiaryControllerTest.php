<?php
declare(strict_types=1);

namespace Tests\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Entity\Beneficiary;
use Entity\Community;
use Entity\Household;
use Entity\Institution;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Entity\NationalId;
use Enum\AssistanceTargetType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Enum\NationalIdType;
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
        $this->client = self::$container->get('test.client');
    }

    public function testGetAssistanceBeneficiariesByAssistance()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

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
        } catch (NoResultException $e) {
            $this->markTestSkipped('You need to have at least one assistance with beneficiary in database to complete this test.');
            return;
        }

        $this->request('GET', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-beneficiaries?sort[]=id.desc');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*", 
            "data": [
                {
                    "id": "*",
                    "beneficiaryId": "*",
                    "removed": "*",
                    "justification": "*",
                    "reliefPackageIds": "*"
                }
            ]}', $this->client->getResponse()->getContent());
    }

    public function testGetAssistanceInstitutionsByAssistance()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

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
        } catch (NoResultException $e) {
            $this->markTestSkipped('You need to have at least one assistance with institution in database to complete this test.');
            return;
        }

        $this->request('GET', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-institutions?sort[]=id.desc');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*", 
            "data": [
                {
                    "id": "*",
                    "institutionId": "*",
                    "removed": "*",
                    "justification": "*",
                    "reliefPackageIds": "*"
                }
            ]}', $this->client->getResponse()->getContent());
    }

    public function testGetAssistanceCommunitiesByAssistance()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

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
        } catch (NoResultException $e) {
            $this->markTestSkipped('You need to have at least one assistance with community in database to complete this test.');
            return;
        }

        $this->request('GET', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-communities?sort[]=id.desc');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*", 
            "data": [
                {
                    "id": "*",
                    "communityId": "*",
                    "removed": "*",
                    "justification": "*",
                    "reliefPackageIds": "*"
                }
            ]}', $this->client->getResponse()->getContent());
    }

    /**
     * @throws Exception
     */
    public function testAddBeneficiaryToAssistance()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $assistance = $em->getRepository(Assistance::class)->findOneBy([
            'validatedBy' => null,
            'completed' => false,
            'archived' => false,
            'targetType' => AssistanceTargetType::INDIVIDUAL,
        ], ['id' => 'asc']);
        $beneficiary = $em->getRepository(Beneficiary::class)->findOneBy([], ['id'=>'desc']);
        $target = $em->getRepository(AssistanceBeneficiary::class)->findOneBy([
            'beneficiary' => $beneficiary,
            'assistance' => $assistance,
        ], ['id'=>'asc']);
        if ($target) {
            $em->remove($target);
            $em->flush();
        }

        $this->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistance->getId().'/assistances-beneficiaries', [
            'beneficiaryIds' => [$beneficiary->getId()],
            'justification' => 'test',
            'added' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        return [$assistance->getId(), $beneficiary->getId()];
    }

    /**
     * @depends testAddBeneficiaryToAssistance
     */
    public function testRemoveBeneficiaryFromAssistance($data)
    {
        list($assistanceId, $beneficiaryId) = $data;

        $this->request('DELETE', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-beneficiaries', [
            'beneficiaryIds' => [$beneficiaryId],
            'justification' => 'test',
            'removed' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->request('GET', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-beneficiaries?sort[]=id.desc');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $result = json_decode($this->client->getResponse()->getContent(), true);
        foreach ($result['data'] as $data) {
            if ($data['beneficiaryId'] == $beneficiaryId) {
                $this->assertTrue($data['removed'], "Target $beneficiaryId wasn't removed");
            }
        }
    }

    public function testAddIndividualWithDocumentToAssistance(): array
    {
        $idNumber = 'tax123';
        $idType = NationalIdType::TAX_NUMBER;
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        
        //get assistance & bnf
        $assistance = $em->getRepository(Assistance::class)->findOneBy([
            'validatedBy' => null,
            'completed' => false,
            'archived' => false,
            'targetType' => AssistanceTargetType::INDIVIDUAL,
        ], ['id' => 'asc']);
        /** @var Beneficiary $beneficiary */
        $beneficiary = $em->getRepository(Beneficiary::class)->findOneBy([], ['id'=>'desc']);
        
        //add tax id to bnf
        $beneficiary->getPerson()->setNationalIds(new ArrayCollection([
            (new NationalId())
                ->setIdNumber($idNumber)
                ->setIdType($idType)
                ->setPerson($beneficiary->getPerson())
        ]));
        $em->persist($beneficiary->getPerson());
        $em->flush();
        
        //remove bnf if in assistance already
        $target = $em->getRepository(AssistanceBeneficiary::class)->findOneBy([
            'beneficiary' => $beneficiary,
            'assistance' => $assistance,
        ], ['id'=>'asc']);
        if ($target) {
            $em->remove($target);
            $em->flush();
        }

        //add bnf to assistance
        $this->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistance->getId().'/assistances-beneficiaries', [
            'documentNumbers' => [$idNumber],
            'documentType' => $idType,
            'justification' => 'test',
            'added' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        return [$assistance->getId(), $beneficiary->getId(), $idNumber, $idType];
    }

    /**
     * @depends testAddIndividualWithDocumentToAssistance
     */
    public function testRemoveIndividualWithDocumentFromAssistance($data): void
    {
        [$assistanceId, $beneficiaryId, $idNumber, $idType] = $data;

        $this->request('DELETE', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-beneficiaries', [
            'documentNumbers' => [$idNumber],
            'documentType' => $idType,
            'justification' => 'test',
            'removed' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->request('GET', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-beneficiaries?sort[]=id.desc');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $result = json_decode($this->client->getResponse()->getContent(), true);
        foreach ($result['data'] as $resultData) {
            if ($resultData['beneficiaryId'] === $beneficiaryId) {
                $this->assertTrue($resultData['removed'], "Target $beneficiaryId wasn't removed ($idType: $idNumber)");
            }
        }
    }

    public function testAddHouseholdWithDocumentsToAssistance(): array
    {
        $idPrefix = 'taxH';
        $idType = NationalIdType::TAX_NUMBER;
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $bnfTaxIds = [];
        $hhTaxId = '';
        $hhBeneficiary = null;

        //get assistance & household
        $assistance = $em->getRepository(Assistance::class)->findOneBy([
            'validatedBy' => null,
            'completed' => false,
            'archived' => false,
            'targetType' => AssistanceTargetType::HOUSEHOLD,
        ], ['id' => 'asc']);
        /** @var Beneficiary $beneficiary */
        
        $q = $em->getRepository(Beneficiary::class)->createQueryBuilder('bnf')
            ->select(['IDENTITY(bnf.household) as hhId', 'COUNT(bnf.id) as cnt'])
            ->groupBy('bnf.household')
            ->having('cnt > 1')->setMaxResults(1);
        
        $hhId = $q->getQuery()->getOneOrNullResult()['hhId'];

        /** @var Household $household */
        $household = $em->getRepository(Household::class)->findOneBy(['id' => $hhId]);
        
        foreach ($household->getBeneficiaries() as $beneficiary) {
            //add tax id to bnf in household
            $beneficiary->getPerson()->setNationalIds(new ArrayCollection([
                (new NationalId())
                    ->setIdNumber($idPrefix . $beneficiary->getId())
                    ->setIdType($idType)
                    ->setPerson($beneficiary->getPerson())
            ]));
            $em->persist($beneficiary->getPerson());

            $beneficiary->isHead() ? $hhTaxId = $idPrefix . $beneficiary->getId() : $bnfTaxIds[] = $idPrefix . $beneficiary->getId();
            
            //remove head from assistance
            if ($beneficiary->isHead()) {
                $hhBeneficiary = $beneficiary;
                $target = $em->getRepository(AssistanceBeneficiary::class)->findOneBy([
                    'beneficiary' => $beneficiary,
                    'assistance' => $assistance,
                ], ['id'=>'asc']);
                if ($target) {
                    $em->remove($target);
                    $em->flush();
                }
            }
        }
        $em->flush();

        //add household head to hh assistance
        $this->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistance->getId().'/assistances-beneficiaries', [
            'documentNumbers' => [$hhTaxId],
            'documentType' => $idType,
            'justification' => 'test',
            'added' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        //add regular bnf to hh assistance
        $this->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistance->getId().'/assistances-beneficiaries', [
            'documentNumbers' => $bnfTaxIds,
            'documentType' => $idType,
            'justification' => 'test',
            'added' => true,
        ]);
        
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertJsonFragment('{
            "failed": [
                "*"
             ]
        }', $this->client->getResponse()->getContent());

        return [$assistance->getId(), $bnfTaxIds, $hhBeneficiary->getId(), $hhTaxId, $idType];
    }

    /**
     * @depends testAddHouseholdWithDocumentsToAssistance
     */
    public function testRemoveHouseholdWithDocumentFromAssistance($data): void
    {
        [$assistanceId, $bnfTaxIds, $hhId, $hhTaxId, $idType] = $data;

        $this->request('DELETE', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-beneficiaries', [
            'documentNumbers' => array_merge($bnfTaxIds, [$hhTaxId]),
            'documentType' => $idType,
            'justification' => 'test',
            'removed' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame(
            count($result['success']),
            1,
            'Removed more beneficiaries - should remove: ' . $hhTaxId . ', removed ' . json_encode($result['success'])
        );

        $this->assertSame(
            count($result['notFound']) + count($result['success']) + count($result['failed']) + count($result['alreadyRemoved']),
            count($bnfTaxIds) + 1,
            'Lost ids, input: ' . $hhTaxId . ',' . implode(',', $bnfTaxIds) . ' output: '  . $this->client->getResponse()->getContent()
        );

        $this->request('GET','/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-beneficiaries?sort[]=id.desc');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $result = json_decode($this->client->getResponse()->getContent(), true);
        foreach ($result['data'] as $resultData) {
            if ($resultData['beneficiaryId'] === $hhId) {
                $this->assertTrue($resultData['removed'], "Target $hhId wasn't removed ($idType: $hhTaxId)");
            }
        }
    }

    /**
     * @throws Exception
     */
    public function testAddInstitutionToAssistance()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $assistance = $em->getRepository(Assistance::class)->findOneBy([
            'validatedBy' => null,
            'completed' => false,
            'archived' => false,
            'targetType' => AssistanceTargetType::INSTITUTION,
        ], ['id' => 'asc']);
        $institution = $em->getRepository(Institution::class)->findOneBy([], ['id'=>'desc']);

        $this->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistance->getId().'/assistances-institutions', [
            'institutionIds' => [$institution->getId()],
            'justification' => 'test',
            'added' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        return [$assistance->getId(), $institution->getId()];
    }

    /**
     * @depends testAddInstitutionToAssistance
     */
    public function testRemoveInstitutionFromAssistance($data)
    {
        list($assistanceId, $institutionId) = $data;

        $this->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-institutions', [
            'institutionIds' => [$institutionId],
            'justification' => 'test',
            'removed' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->request('GET', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-institutions?sort[]=id.desc');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $result = json_decode($this->client->getResponse()->getContent(), true);
        foreach ($result['data'] as $data) {
            if ($data['institutionId'] == $institutionId) {
                $this->assertTrue($data['removed'], "Target $institutionId wasn't removed");
            }
        }
    }

    /**
     * @throws Exception
     */
    public function testAddCommunityToAssistance()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $assistance = $em->getRepository(Assistance::class)->findOneBy([
            'validatedBy' => null,
            'completed' => false,
            'archived' => false,
            'targetType' => AssistanceTargetType::COMMUNITY,
        ], ['id' => 'asc']);
        $community = $em->getRepository(Community::class)->findOneBy([], ['id'=>'desc']);

        $this->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistance->getId().'/assistances-communities', [
            'communityIds' => [$community->getId()],
            'justification' => 'test',
            'added' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        return [$assistance->getId(), $community->getId()];
    }

    /**
     * @depends testAddCommunityToAssistance
     */
    public function testRemoveCommunityFromAssistance($data)
    {
        list($assistanceId, $communityId) = $data;

        $this->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-communities', [
            'communityIds' => [$communityId],
            'justification' => 'test',
            'removed' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->request('GET', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-communities?sort[]=id.desc');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $result = json_decode($this->client->getResponse()->getContent(), true);
        foreach ($result['data'] as $data) {
            if ($data['communityId'] == $communityId) {
                $this->assertTrue($data['removed'], "Target $communityId wasn't removed");
            }
        }
    }
}
