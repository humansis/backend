<?php declare(strict_types=1);

namespace Tests\Validator\Constraints;

use Entity\Beneficiary;
use Component\Import\ImportFileValidator;
use Component\Import\ImportService;
use Component\Import\UploadImportService;
use Entity\Import;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomValidatorTest extends KernelTestCase
{
    /** @var ValidatorInterface */
    private $validator;

    protected function setUp()
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->validator = $kernel->getContainer()->get('validator');
    }


    public function data(): iterable
    {
        yield ['x', 'x', 'x', 3];
        yield ['2000-12-01T00:00:00.000Z', '31-12-2000', 'SYR', 0];
        yield ['31-12-2000', '2000-12-01T00:00:00.000Z', 'QQQ', 3];
    }

    /**
     * @dataProvider data
     */
    public function testValidate(?string $isoDate, ?string $importDate, ?string $countryISO, int $validationErrorExpectation)
    {
        $object = new TestDummyObject($isoDate, $importDate, $countryISO);
        $violations = $this->validator->validate($object);
        $this->assertCount($validationErrorExpectation, $violations);

        $object = new TestGroupedObject($isoDate, $importDate, $countryISO);
        $violations = $this->validator->validate($object);
        $this->assertCount($validationErrorExpectation, $violations);
    }

    public function dataWithGroups(): iterable
    {
        $groups = ['date' => 2, 'iso' => 2, 'country' => 1, 'isodate' => 1, 'importdate' => 1];
        foreach ($groups as $group => $errorCount) {
            yield [$group, 'x', 'x', 'x', $errorCount];
        }
        foreach ($groups as $group => $errorCount) {
            yield [$group, '2000-12-01T00:00:00.000Z', '31-12-2000', 'SYR', 0];
        }
    }

    /**
     * @dataProvider dataWithGroups
     */
    public function testValidateByGroup(string $groupName, ?string $isoDate, ?string $importDate, ?string $countryISO, int $validationErrorExpectation)
    {
        $object = new TestGroupedObject($isoDate, $importDate, $countryISO);
        $violations = $this->validator->validate($object, null, $groupName);
        $this->assertCount($validationErrorExpectation, $violations);
    }
}
