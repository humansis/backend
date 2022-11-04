<?php

namespace Tests\Utils;

use Doctrine\Persistence\ObjectManager;
use Entity\Project;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Entity\Booklet;
use Utils\BookletGenerator;

class BookletGeneratorTest extends KernelTestCase
{
    /** @var ObjectManager|null */
    private $em;

    private \Utils\BookletGenerator $generator;

    public function setUp(): void
    {
        self::bootKernel();

        //Preparing the EntityManager
        $this->em = self::getContainer()
            ->get('doctrine')
            ->getManager();

        // clean testing booklets, if exists
        $result = $this->em->getRepository(Booklet::class)->findBy(['countryIso3' => 'TST'], ['id' => 'asc']);
        foreach ($result as $item) {
            $this->em->remove($item);
        }
        $this->em->flush();

        $this->generator = new BookletGenerator($this->em);
    }

    public function testGenerateOneBooklet(): void
    {
        $project = $this->em->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        $this->generator->generate($project, 'TST', 1, 1, 'USD', [10]);

        /** @var Booklet[] $result */
        $result = $this->em->getRepository(Booklet::class)->findBy(['countryIso3' => 'TST'], ['id' => 'asc']);

        $this->assertCount(1, $result, 'Number of generated booklets is not correct.');
        $this->assertCount(1, $result[0]->getVouchers(), 'Booklet should contains one voucher');
        $this->assertEquals(10, $result[0]->getTotalValue(), 'Booklet total value should be 10');
    }

    public function testGenerateOneBookletMultipleVouchers(): void
    {
        $project = $this->em->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        $this->generator->generate($project, 'TST', 1, 3, 'USD', [10]);

        /** @var Booklet[] $result */
        $result = $this->em->getRepository(Booklet::class)->findBy(['countryIso3' => 'TST'], ['id' => 'asc']);

        $this->assertCount(1, $result, 'Number of generated booklets is not correct.');
        $this->assertCount(3, $result[0]->getVouchers()->toArray(), 'Booklet should contains three vouchers');
        $this->assertEquals(30, $result[0]->getTotalValue(), 'Booklet total value should be 30');
        $this->assertEquals(10, $result[0]->getVouchers()->get(0)->getValue(), 'Value of voucher should be 10');
        $this->assertEquals(10, $result[0]->getVouchers()->get(1)->getValue(), 'Value of voucher should be 10');
        $this->assertEquals(10, $result[0]->getVouchers()->get(2)->getValue(), 'Value of voucher should be 10');
    }

    public function testGenerateOneBookletMultipleVouchersDifferentValues(): void
    {
        $project = $this->em->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        $this->generator->generate($project, 'TST', 1, 3, 'USD', [10, 20, 30]);

        /** @var Booklet[] $result */
        $result = $this->em->getRepository(Booklet::class)->findBy(['countryIso3' => 'TST'], ['id' => 'asc']);

        $this->assertCount(1, $result, 'Number of generated booklets is not correct.');
        $this->assertCount(3, $result[0]->getVouchers()->toArray(), 'Booklet should contains three vouchers');
        $this->assertEquals(60, $result[0]->getTotalValue(), 'Booklet total value should be 60');
        $this->assertEquals(10, $result[0]->getVouchers()->get(0)->getValue(), 'Value of voucher should be 10');
        $this->assertEquals(20, $result[0]->getVouchers()->get(1)->getValue(), 'Value of voucher should be 20');
        $this->assertEquals(30, $result[0]->getVouchers()->get(2)->getValue(), 'Value of voucher should be 30');
    }

    public function testGenerateOneBookletMultipleVouchersWithLessValues(): void
    {
        $project = $this->em->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        $this->generator->generate($project, 'TST', 1, 3, 'USD', [10, 20]);

        /** @var Booklet[] $result */
        $result = $this->em->getRepository(Booklet::class)->findBy(['countryIso3' => 'TST'], ['id' => 'asc']);

        $this->assertCount(1, $result, 'Number of generated booklets is not correct.');
        $this->assertCount(3, $result[0]->getVouchers()->toArray(), 'Booklet should contains three vouchers');
        $this->assertEquals(50, $result[0]->getTotalValue(), 'Booklet total value should be 50');
        $this->assertEquals(10, $result[0]->getVouchers()->get(0)->getValue(), 'Value of voucher should be 10');
        $this->assertEquals(20, $result[0]->getVouchers()->get(1)->getValue(), 'Value of voucher should be 20');
        $this->assertEquals(20, $result[0]->getVouchers()->get(2)->getValue(), 'Value of voucher should be 20');
    }

    public function testGenerateMultipleBooklets(): void
    {
        $project = $this->em->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        $this->generator->generate($project, 'TST', 2, 1, 'USD', [10]);

        /** @var Booklet[] $result */
        $result = $this->em->getRepository(Booklet::class)->findBy(['countryIso3' => 'TST'], ['id' => 'asc']);

        $this->assertCount(2, $result, 'Number of generated booklets is not correct.');
        $this->assertCount(1, $result[0]->getVouchers()->toArray(), 'Booklet 1 should contains one voucher');
        $this->assertCount(1, $result[1]->getVouchers()->toArray(), 'Booklet 2 should contains one voucher');
        $this->assertEquals(10, $result[0]->getTotalValue(), 'Booklet 1 total value should be 10');
        $this->assertEquals(10, $result[1]->getTotalValue(), 'Booklet 2 total value should be 10');
    }

    public function testGenerateMultipleBookletsMultipleVouchers(): void
    {
        $project = $this->em->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        $this->generator->generate($project, 'TST', 2, 3, 'USD', [10]);

        /** @var Booklet[] $result */
        $result = $this->em->getRepository(Booklet::class)->findBy(['countryIso3' => 'TST'], ['id' => 'asc']);

        $this->assertCount(2, $result, 'Number of generated booklets is not correct.');
        $this->assertCount(3, $result[0]->getVouchers()->toArray(), 'Booklet 1 should contains three vouchers');
        $this->assertCount(3, $result[1]->getVouchers()->toArray(), 'Booklet 2 should contains three vouchers');
        $this->assertEquals(30, $result[0]->getTotalValue(), 'Booklet 1 total value should be 30');
        $this->assertEquals(30, $result[1]->getTotalValue(), 'Booklet 2 total value should be 30');
        $this->assertEquals(10, $result[0]->getVouchers()->get(0)->getValue(), 'Value of voucher should be 10');
        $this->assertEquals(10, $result[0]->getVouchers()->get(1)->getValue(), 'Value of voucher should be 10');
        $this->assertEquals(10, $result[0]->getVouchers()->get(2)->getValue(), 'Value of voucher should be 10');
    }

    public function testGenerateMultipleBookletsMultipleVouchersDifferentValues(): void
    {
        $project = $this->em->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        $this->generator->generate($project, 'TST', 2, 3, 'USD', [10, 20, 30]);

        /** @var Booklet[] $result */
        $result = $this->em->getRepository(Booklet::class)->findBy(['countryIso3' => 'TST'], ['id' => 'asc']);

        $this->assertCount(2, $result, 'Number of generated booklets is not correct.');
        $this->assertCount(3, $result[0]->getVouchers()->toArray(), 'Booklet 1 should contains three vouchers');
        $this->assertCount(3, $result[1]->getVouchers()->toArray(), 'Booklet 2 should contains three vouchers');
        $this->assertEquals(60, $result[0]->getTotalValue(), 'Booklet 1 total value should be 60');
        $this->assertEquals(60, $result[1]->getTotalValue(), 'Booklet 2 total value should be 60');
        $this->assertEquals(10, $result[0]->getVouchers()->get(0)->getValue(), 'Value of voucher should be 10');
        $this->assertEquals(20, $result[0]->getVouchers()->get(1)->getValue(), 'Value of voucher should be 20');
        $this->assertEquals(30, $result[0]->getVouchers()->get(2)->getValue(), 'Value of voucher should be 30');
    }

    public function testGenerateMultipleBookletsMultipleVouchersWithLessValues(): void
    {
        $project = $this->em->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        $this->generator->generate($project, 'TST', 2, 3, 'USD', [10, 20]);

        /** @var Booklet[] $result */
        $result = $this->em->getRepository(Booklet::class)->findBy(['countryIso3' => 'TST'], ['id' => 'asc']);

        $this->assertCount(2, $result, 'Number of generated booklets is not correct.');
        $this->assertCount(3, $result[0]->getVouchers()->toArray(), 'Booklet 1 should contains three vouchers');
        $this->assertCount(3, $result[1]->getVouchers()->toArray(), 'Booklet 2 should contains three vouchers');
        $this->assertEquals(50, $result[0]->getTotalValue(), 'Booklet 1 total value should be 50');
        $this->assertEquals(50, $result[1]->getTotalValue(), 'Booklet 2 total value should be 50');
        $this->assertEquals(10, $result[0]->getVouchers()->get(0)->getValue(), 'Value of voucher should be 10');
        $this->assertEquals(20, $result[0]->getVouchers()->get(1)->getValue(), 'Value of voucher should be 20');
        $this->assertEquals(20, $result[0]->getVouchers()->get(2)->getValue(), 'Value of voucher should be 20');
    }
}
