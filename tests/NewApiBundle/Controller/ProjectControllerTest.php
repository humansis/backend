<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use NewApiBundle\Enum\ProductCategoryType;
use ProjectBundle\DBAL\SectorEnum;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class ProjectControllerTest extends AbstractFunctionalApiTest
{
    /** @var string  */
    private $projectName;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->projectName = 'Test project No. '.time();
    }

    public function testCreate()
    {
        $this->client->request('POST', '/api/basic/web-app/v1/projects', $data = [
            'name' => $this->projectName,
            'internalId' => 'PT23',
            'iso3' => 'KHM',
            'target' => 10,
            'startDate' => '2010-10-10T00:00:00+0000',
            'endDate' => '2011-10-10T00:00:00+0000',
            'sectors' => [SectorEnum::FOOD_SECURITY],
            'projectInvoiceAddressLocal' => 'Local invoice address',
            'projectInvoiceAddressEnglish' => 'English invoice address',
            'allowedProductCategoryTypes' => [
                ProductCategoryType::FOOD,
                ProductCategoryType::NONFOOD,
            ],
        ], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('internalId', $result);
        $this->assertArrayHasKey('iso3', $result);
        $this->assertArrayHasKey('notes', $result);
        $this->assertArrayHasKey('target', $result);
        $this->assertArrayHasKey('startDate', $result);
        $this->assertArrayHasKey('endDate', $result);
        $this->assertArrayHasKey('sectors', $result);
        $this->assertArrayHasKey('donorIds', $result);
        $this->assertArrayHasKey('numberOfHouseholds', $result);
        $this->assertArrayHasKey('deletable', $result);
        $this->assertContains(SectorEnum::FOOD_SECURITY, $result['sectors']);
        $this->assertSame([], $result['donorIds']);
        $this->assertArrayHasKey('projectInvoiceAddressLocal', $result);
        $this->assertArrayHasKey('projectInvoiceAddressEnglish', $result);

        $this->assertArrayHasKey('allowedProductCategoryTypes', $result);
        $this->assertEquals($data['allowedProductCategoryTypes'], $result['allowedProductCategoryTypes']);

        return $result['id'];
    }

    /**
     * @depends testCreate
     */
    public function testSummaries($id)
    {
        $this->client->request('GET', '/api/basic/web-app/v1/projects/'.$id.'/summaries?code[]=reached_beneficiaries', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);

        foreach ($result['data'] as $item) {
            $this->assertArrayHasKey('code', $item);
            $this->assertArrayHasKey('value', $item);
        }
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(int $id)
    {
        $this->client->request('PUT', '/api/basic/web-app/v1/projects/'.$id, $data = [
            'name' => $this->projectName,
            'internalId' => 'TPX',
            'iso3' => 'KHM',
            'target' => 10,
            'startDate' => '2010-10-10T00:00:00+0000',
            'endDate' => '2011-10-10T00:00:00+0000',
            'sectors' => [SectorEnum::EARLY_RECOVERY, SectorEnum::CAMP_MANAGEMENT],
            'projectInvoiceAddressLocal' => 'Local invoice address',
            'projectInvoiceAddressEnglish' => 'English invoice address',
            'allowedProductCategoryTypes' => [
                ProductCategoryType::CASHBACK,
                ProductCategoryType::NONFOOD,
            ],
        ], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('internalId', $result);
        $this->assertArrayHasKey('iso3', $result);
        $this->assertArrayHasKey('notes', $result);
        $this->assertArrayHasKey('target', $result);
        $this->assertArrayHasKey('startDate', $result);
        $this->assertArrayHasKey('endDate', $result);
        $this->assertArrayHasKey('sectors', $result);
        $this->assertArrayHasKey('donorIds', $result);
        $this->assertArrayHasKey('numberOfHouseholds', $result);
        $this->assertArrayHasKey('deletable', $result);
        $this->assertContains(SectorEnum::EARLY_RECOVERY, $result['sectors']);
        $this->assertContains(SectorEnum::CAMP_MANAGEMENT, $result['sectors']);
        $this->assertNotContains(SectorEnum::FOOD_SECURITY, $result['sectors']);
        $this->assertArrayHasKey('projectInvoiceAddressLocal', $result);
        $this->assertArrayHasKey('projectInvoiceAddressEnglish', $result);

        $this->assertArrayHasKey('allowedProductCategoryTypes', $result);
        $this->assertEquals($data['allowedProductCategoryTypes'], $result['allowedProductCategoryTypes']);

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testGet(int $id)
    {
        $this->client->request('GET', '/api/basic/web-app/v1/projects/'.$id, [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('internalId', $result);
        $this->assertArrayHasKey('iso3', $result);
        $this->assertArrayHasKey('notes', $result);
        $this->assertArrayHasKey('target', $result);
        $this->assertArrayHasKey('startDate', $result);
        $this->assertArrayHasKey('endDate', $result);
        $this->assertArrayHasKey('sectors', $result);
        $this->assertArrayHasKey('donorIds', $result);
        $this->assertArrayHasKey('numberOfHouseholds', $result);
        $this->assertArrayHasKey('deletable', $result);
        $this->assertArrayHasKey('projectInvoiceAddressLocal', $result);
        $this->assertArrayHasKey('projectInvoiceAddressEnglish', $result);
        $this->assertArrayHasKey('allowedProductCategoryTypes', $result);

        return $id;
    }

    /**
     * @depends testGet
     */
    public function testGetList($id)
    {
        $this->client->request('GET', '/api/basic/web-app/v1/projects?filter[id][]='.$id.'&filter[fulltext]='.$this->projectName, [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);

        return $id;
    }

    /**
     * @depends testGet
     */
    public function testDelete(int $id)
    {
        $this->client->request('DELETE', '/api/basic/web-app/v1/projects/'.$id, [], [], $this->addAuth());

        $this->assertTrue($this->client->getResponse()->isEmpty());

        return $id;
    }

    /**
     * @depends testDelete
     */
    public function testGetNotexists(int $id)
    {
        $this->client->request('GET', '/api/basic/web-app/v1/projects/'.$id, [], [], $this->addAuth());

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }
}
