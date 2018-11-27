<?php

namespace Tests\BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\CountrySpecific;
use CommonBundle\Utils\ExportService;
use Tests\BMSServiceTestCase;



class BeneficiaryControllerTest extends BMSServiceTestCase {

    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("jms_serializer");
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');
    }

    public function getCountrySpecificsActionTest() {

    }

    public function createAction() {

    }

    public function updateAction() {

    }

    public function deleteAction() {
        
    }

}