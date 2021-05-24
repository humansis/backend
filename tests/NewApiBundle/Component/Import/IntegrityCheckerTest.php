<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\IntegrityChecker;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportFile;
use NewApiBundle\Entity\ImportQueue;
use ProjectBundle\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UserBundle\Entity\User;

class IntegrityCheckerTest extends KernelTestCase
{
    /** @var ValidatorInterface */
    private static $validator;

    /** @var EntityManagerInterface */
    private static $entityManager;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $kernel = self::bootKernel();

        self::$validator = $kernel->getContainer()->get('validator');
        self::$entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testParse()
    {
        $project = self::$entityManager->getRepository(Project::class)->findBy(['archived' => false, 'iso3' => 'KHM'], null, 1)[0];
        $user = self::$entityManager->getRepository(User::class)->findBy([], null, 1)[0];

        $import = new Import('test', null, $project, $user);
        $file = new ImportFile('fake_file.xlsx', $import, $user);

        $item = new ImportQueue($import, $file, [[/** empty row */]]);

        $checker = new IntegrityChecker(self::$validator, self::$entityManager);

        $method = new \ReflectionMethod($checker, 'checkOne');
        $method->setAccessible(true);
        $method->invoke($checker, $item);

       $this->assertJson($item->getMessage());
    }
}
