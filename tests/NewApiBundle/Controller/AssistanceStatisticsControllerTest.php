<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use DistributionBundle\Entity\Assistance;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class AssistanceStatisticsControllerTest extends AbstractFunctionalApiTest
{
    public function testStatistics()
    {
        /** @var Assistance $assistance */
        $assistance = self::$container->get('doctrine')->getRepository(Assistance::class)->findBy([], ['id' => 'asc'])[0];

        $this->client->request('GET', '/api/basic/web-app/v1/assistances/'.$assistance->getId().'/statistics', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "id": '.$assistance->getId().',
            "numberOfBeneficiaries": "*",
            "amountTotal": "*",
            "amountDistributed": "*",
            "amountUsed": "*",
            "amountSent": "*",
            "amountPickedUp": "*"
        }', $this->client->getResponse()->getContent());
    }

    public function testList()
    {
        /** @var Assistance $assistance */
        $assistance = self::$container->get('doctrine')->getRepository(Assistance::class)->findBy(['archived' => false], ['id' => 'asc'])[0];

        $this->client->request('GET', '/api/basic/web-app/v1/assistances/statistics?filter[id][]='.$assistance->getId(), ['country' => 'KHM'], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }
}
