<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use DistributionBundle\Enum\AssistanceTargetType;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ProjectBundle\Entity\Project;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class BeneficiaryControllerTest extends AbstractFunctionalApiTest
{
    /**
     * @throws Exception
     */
    public function testGetBeneficiary()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $beneficiary = $em->getRepository(Beneficiary::class)->findBy([], ['id' => 'asc'])[0];

        $this->client->request('GET', '/api/basic/web-app/v1/beneficiaries/'.$beneficiary->getId(), [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('dateOfBirth', $result);
        $this->assertArrayHasKey('localFamilyName', $result);
        $this->assertArrayHasKey('localGivenName', $result);
        $this->assertArrayHasKey('localParentsName', $result);
        $this->assertArrayHasKey('enFamilyName', $result);
        $this->assertArrayHasKey('enGivenName', $result);
        $this->assertArrayHasKey('enParentsName', $result);
        $this->assertArrayHasKey('gender', $result);
        $this->assertArrayHasKey('nationalIds', $result);
        $this->assertArrayHasKey('phoneIds', $result);
        $this->assertArrayHasKey('referralType', $result);
        $this->assertArrayHasKey('referralComment', $result);
        $this->assertArrayHasKey('residencyStatus', $result);
        $this->assertArrayHasKey('isHead', $result);
        $this->assertArrayHasKey('vulnerabilityCriteria', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetBeneficiaries()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $beneficiary = $em->getRepository(Beneficiary::class)->findBy([], ['id' => 'asc'])[0];

        $this->client->request('GET', '/api/basic/web-app/v1/beneficiaries?filter[id][]='.$beneficiary->getId(), [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['totalCount']);
    }

    public function testAddReferral()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $bnfId = $em->createQueryBuilder()
            ->select('b.id')
            ->from(Beneficiary::class, 'b')
            ->join('b.person', 'p')
            ->leftJoin('p.referral', 'r')
            ->where('r.id IS NULL')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleScalarResult();

        $this->client->request('PATCH', '/api/basic/web-app/v1/beneficiaries/'.$bnfId, [
            'referralType' => \BeneficiaryBundle\Entity\Referral::types()[0],
            'referralComment' => 'test status',
        ], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "id": "*",
            "dateOfBirth": "*",
            "localFamilyName": "*",
            "localGivenName": "*",
            "localParentsName": "*",
            "enFamilyName": "*",
            "enGivenName": "*",
            "enParentsName": "*",
            "gender": "*",
            "nationalIds": "*",
            "phoneIds": "*",
            "referralType": "1",
            "referralComment": "test status",
            "residencyStatus": "*",
            "isHead": "*",
            "vulnerabilityCriteria": "*"
        }', $this->client->getResponse()->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetNationalId()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $nationalId = $em->getRepository(NationalId::class)->findBy([], ['id' => 'asc'])[0];

        $this->client->request('GET', '/api/basic/web-app/v1/beneficiaries/national-ids/'.$nationalId->getId(), [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('number', $result);
        $this->assertArrayHasKey('type', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetNationalIds()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $nationalId = $em->getRepository(NationalId::class)->findBy([], ['id' => 'asc'])[0];

        $this->client->request('GET', '/api/basic/web-app/v1/beneficiaries/national-ids?filter[id][]='.$nationalId->getId(), [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{"totalCount": 1, "data": [{"id": "*"}]}', $this->client->getResponse()->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetPhone()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $phone = $em->getRepository(Phone::class)->findBy([], ['id' => 'asc'])[0];

        $this->client->request('GET', '/api/basic/web-app/v1/beneficiaries/phones/'.$phone->getId(), [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('number', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('prefix', $result);
        $this->assertArrayHasKey('proxy', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetPhones()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $phone1 = $em->getRepository(Phone::class)->findBy([], ['id' => 'asc'])[0];
        $phone2 = $em->getRepository(Phone::class)->findBy([], ['id' => 'asc'])[1];

        $this->client->request('GET', '/api/basic/web-app/v1/beneficiaries/phones?filter[id][]='.$phone1->getId().'&filter[id][]='.$phone2->getId(), [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": 2, 
            "data": [{"id": '.$phone1->getId().'}, {"id": '.$phone2->getId().'}
            ]}', $this->client->getResponse()->getContent());
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetBeneficiariesByProject()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $project = $em->getRepository(Project::class)->findOneBy([
            'archived' => false,
        ], ['id' => 'asc']);

        $this->client->request('GET', '/api/basic/web-app/v1/projects/'.$project->getId().'/targets/'.AssistanceTargetType::INDIVIDUAL.'/beneficiaries', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": "*", 
            "data": [
                {
                    "id": "*",
                    "dateOfBirth": "*",
                    "localFamilyName": "*",
                    "localGivenName": "*",
                    "localParentsName": "*",
                    "enFamilyName": "*",
                    "enGivenName": "*",
                    "enParentsName": "*",
                    "gender": "*",
                    "nationalIds": "*",
                    "phoneIds": "*",
                    "referralType": "*",
                    "referralComment": "*",
                    "residencyStatus": "*",
                    "isHead": "*",
                    "vulnerabilityCriteria": "*"
                }
            ]}', $this->client->getResponse()->getContent());
    }
}
