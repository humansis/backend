<?php

namespace Tests\Controller;

use Entity\Camp;
use Doctrine\ORM\NoResultException;
use Exception;
use Tests\BMSServiceTestCase;

class CampControllerTest extends BMSServiceTestCase
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

    public function testCamps()
    {
        $this->request('GET', '/api/basic/web-app/v1/camps');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "totalCount": "*",
            "data": "*"}',
            $this->client->getResponse()->getContent()
        );
    }

    public function testCamp()
    {
        try {
            $campId = $this->em->createQueryBuilder()
                ->select('c.id')
                ->from(Camp::class, 'c')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
            $this->markTestSkipped('You need to have at least one camp with location in system');

            return;
        }

        $this->request('GET', '/api/basic/web-app/v1/camps/' . $campId);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "id": ' . $campId . ',
            "name": "*",
            "locationId": "*",
            "adm1Id": "*",
            "adm2Id": "*",
            "adm3Id": "*",
            "adm4Id": "*"
        }',
            $this->client->getResponse()->getContent()
        );
    }

    public function testCampsByLocation()
    {
        try {
            $locationId = $this->em->createQueryBuilder()
                ->select('l.id')
                ->from(Camp::class, 'c')
                ->leftJoin('c.location', 'l')
                ->andWhere('l.id IS NOT NULL')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
            $this->markTestSkipped('You need to have at least one camp with location in system');

            return;
        }

        $this->request('GET', "/api/basic/web-app/v1/locations/$locationId/camps");

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
                    "locationId": "*",
                    "adm1Id": "*",
                    "adm2Id": "*",
                    "adm3Id": "*",
                    "adm4Id": "*"
                }
            ]}',
            $this->client->getResponse()->getContent()
        );
    }
}
