<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use DistributionBundle\Entity\GeneralReliefItem;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class GeneralReliefItemControllerTest extends AbstractFunctionalApiTest
{
    public function testGet()
    {
        /** @var GeneralReliefItem $item */
        $item = self::$container->get('doctrine')->getRepository(GeneralReliefItem::class)->findBy([], ['id' => 'asc'])[0];

        $this->client->request('GET', '/api/basic/web-app/v1/general-relief-items/'.$item->getId(), [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonStringEqualsJsonString('{
            "id": '.$item->getId().',
            "distributed": '.(null === $item->getDistributedAt() ? 'false' : 'true').',
            "dateOfDistribution": '.(null === $item->getDistributedAt() ? 'null' : '"'.$item->getDistributedAt()->format(\DateTime::ISO8601).'"').',
            "note": '.(null === $item->getNotes() ? 'null' : '"'.$item->getNotes().'"').'
        }', $this->client->getResponse()->getContent());
    }

    public function testList()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/general-relief-items?&filter[id][]=1', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testPatch()
    {
        /** @var GeneralReliefItem $item */
        $item = self::$container->get('doctrine')->getRepository(GeneralReliefItem::class)->findBy(['distributedAt' => null], ['id' => 'asc'])[0];

        $this->client->request('PATCH', '/api/basic/web-app/v2/general-relief-items/'.$item->getId(), [
            'distributed' => true,
            'dateOfDistribution' => "2020-01-01T10:10:00+00",
            'note' => "some note",
        ], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "id": '.$item->getId().',
            "distributed": true,
            "dateOfDistribution": "*",
            "note": "some note"
        }', $this->client->getResponse()->getContent());
    }

    /**
     * @depends testPatch
     */
    public function testPatch2()
    {
        /** @var GeneralReliefItem $item */
        $item = self::$container->get('doctrine')->getRepository(GeneralReliefItem::class)->findBy(['distributedAt' => new \DateTime('2020-01-01T10:10:00+00')], ['id' => 'asc'])[0];

        $this->client->request('PATCH', '/api/basic/web-app/v2/general-relief-items/'.$item->getId(), [
            'distributed' => false,
        ], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "id": '.$item->getId().',
            "distributed": false,
            "dateOfDistribution": null
        }', $this->client->getResponse()->getContent());
    }
}
