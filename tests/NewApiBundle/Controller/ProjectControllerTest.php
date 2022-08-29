<?php

namespace Tests\NewApiBundle\Controller;

use Exception;
use NewApiBundle\Enum\ProductCategoryType;
use ProjectBundle\DBAL\SectorEnum;
use Tests\BMSServiceTestCase;

class ProjectControllerTest extends BMSServiceTestCase
{
    /** @var string */
    private $projectName;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->projectName = 'Test project No. '.time();
    }

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

    public function testCreate()
    {
        $this->request('POST', '/api/basic/web-app/v1/projects', $data = [
            'name' => $this->projectName,
            'internalId' => 'PT23',
            'iso3' => 'KHM',
            'target' => 10,
            'startDate' => '2010-10-10T00:00:00+0000',
            'endDate' => '2022-10-10T00:00:00+0000',
            'sectors' => [SectorEnum::FOOD_SECURITY],
            'projectInvoiceAddressLocal' => 'Local invoice address',
            'projectInvoiceAddressEnglish' => 'English invoice address',
            'allowedProductCategoryTypes' => [
                ProductCategoryType::FOOD,
                ProductCategoryType::NONFOOD,
            ],
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
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
        $this->request('GET', '/api/basic/web-app/v1/projects/'.$id.'/summaries?code[]=reached_beneficiaries');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

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
        $this->request('PUT', '/api/basic/web-app/v1/projects/'.$id, $data = [
            'name' => $this->projectName,
            'internalId' => 'TPX',
            'iso3' => 'KHM',
            'target' => 10,
            'startDate' => '2010-10-10T00:00:00+0000',
            'endDate' => '2022-10-10T00:00:00+0000',
            'sectors' => [SectorEnum::EARLY_RECOVERY, SectorEnum::CAMP_MANAGEMENT],
            'projectInvoiceAddressLocal' => 'Local invoice address',
            'projectInvoiceAddressEnglish' => 'English invoice address',
            'allowedProductCategoryTypes' => [
                ProductCategoryType::CASHBACK,
                ProductCategoryType::NONFOOD,
            ],
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
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
        $this->request('GET', '/api/basic/web-app/v1/projects/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
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
        $this->request('GET', '/api/basic/web-app/v1/projects?filter[id][]='.$id.'&filter[fulltext]='.$this->projectName);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
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
        $this->request('DELETE', '/api/basic/web-app/v1/projects/'.$id);

        $this->assertTrue($this->client->getResponse()->isEmpty());

        return $id;
    }

    /**
     * @depends testDelete
     */
    public function testGetNotexists(int $id)
    {
        $this->request('GET', '/api/basic/web-app/v1/projects/'.$id);

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }
}
