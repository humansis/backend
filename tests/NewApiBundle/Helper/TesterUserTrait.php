<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;
use UserBundle\Entity\User;

trait TesterUserTrait
{
    protected function addAuth($headers = null): array
    {
        return array_merge([
            'HTTP_COUNTRY' => 'KHM',
            'PHP_AUTH_USER' => 'admin@example.org',
            'PHP_AUTH_PW'   => 'pin1234'
        ], (array) $headers);
    }

    protected function getTestUser(?string $userName): User
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        return $em->getRepository(User::class)->findOneBy(['username'=>$userName], ['id'=>'asc']);
    }
}
