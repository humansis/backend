<?php


namespace Tests\UserBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testGetUsers()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/wsse/users');
        dump($client->getResponse()->getContent());
        $this->assertTrue($client->getResponse()->isSuccessful());
    }
}