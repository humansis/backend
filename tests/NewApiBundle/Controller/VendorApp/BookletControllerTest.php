<?php

namespace Tests\NewApiBundle\Controller\VendorApp;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Institution;
use Doctrine\ORM\NoResultException;
use Exception;
use ProjectBundle\Entity\Project;
use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\Booklet;

class BookletControllerTest extends BMSServiceTestCase
{
    /**
     * @throws Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    public function testGetProtectedBooklet()
    {

        $this->request('GET', '/api/jwt/vendor-app/v2/protected-booklets', [], [], ['Country' => 'KHM']);
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
    }

}
