<?php

namespace Tests\Controller;

use DateTime;
use Entity\GeneralReliefItem;
use Exception;
use Tests\BMSServiceTestCase;

class GeneralReliefItemControllerTest extends BMSServiceTestCase
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

    public function testGet()
    {
        /** @var GeneralReliefItem $item */
        $item = self::$container->get('doctrine')->getRepository(GeneralReliefItem::class)->findOneBy(
            [],
            ['id' => 'asc']
        );

        if (!$item) {
            $this->markTestIncomplete('Missing test data: GRI');
        }

        $this->request('GET', '/api/basic/web-app/v1/general-relief-items/' . $item->getId());

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonStringEqualsJsonString(
            '{
            "id": ' . $item->getId() . ',
            "distributed": ' . (null === $item->getDistributedAt() ? 'false' : 'true') . ',
            "dateOfDistribution": ' . (null === $item->getDistributedAt()
                ? 'null'
                : '"' . $item->getDistributedAt()->format(DateTime::ISO8601) . '"') . ',
            "note": ' . (null === $item->getNotes() ? 'null' : '"' . $item->getNotes() . '"') . '
        }',
            $this->client->getResponse()->getContent()
        );
    }

    public function testList()
    {
        $this->request('GET', '/api/basic/web-app/v1/general-relief-items?&filter[id][]=1');

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testPatch()
    {
        /** @var GeneralReliefItem $item */
        $item = self::$container->get('doctrine')->getRepository(GeneralReliefItem::class)->findOneBy(
            ['distributedAt' => null],
            ['id' => 'asc']
        );

        if (!$item) {
            $this->markTestIncomplete('Missing test data: GRI');
        }

        $this->request('PATCH', '/api/basic/web-app/v2/general-relief-items/' . $item->getId(), [
            'distributed' => true,
            'dateOfDistribution' => "2020-01-01T10:10:00+00",
            'note' => "some note",
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "id": ' . $item->getId() . ',
            "distributed": true,
            "dateOfDistribution": "*",
            "note": "some note"
        }',
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @depends testPatch
     */
    public function testPatch2()
    {
        /** @var GeneralReliefItem $item */
        $item = self::$container->get('doctrine')->getRepository(GeneralReliefItem::class)->findBy(
            ['distributedAt' => new DateTime('2020-01-01T10:10:00+00')],
            ['id' => 'asc']
        )[0];

        $this->request('PATCH', '/api/basic/web-app/v2/general-relief-items/' . $item->getId(), [
            'distributed' => false,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "id": ' . $item->getId() . ',
            "distributed": false,
            "dateOfDistribution": null
        }',
            $this->client->getResponse()->getContent()
        );
    }
}
