<?php

declare(strict_types=1);

namespace Mapper\Import\Duplicity;

use Entity\Beneficiary;
use Component\Import\CellParameters;
use Component\Import\Integrity\ImportLineFactory;
use Component\Import\ValueObject\BeneficiaryCompare;
use Entity\ImportBeneficiaryDuplicity;
use InvalidArgumentException;
use Serializer\MapperInterface;

class BeneficiaryDuplicityMapper implements MapperInterface
{
    /** @var ImportBeneficiaryDuplicity */
    private $object;

    /** @var ImportLineFactory */
    private $importLineFactory;

    /**
     * @param ImportLineFactory $importLineFactory
     */
    public function __construct(ImportLineFactory $importLineFactory)
    {
        $this->importLineFactory = $importLineFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof ImportBeneficiaryDuplicity && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object): void
    {
        if ($object instanceof ImportBeneficiaryDuplicity) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException('Invalid argument. It should be instance of ' . ImportBeneficiaryDuplicity::class . ', ' . get_class($object) . ' given.');
    }

    public function getReasons(): iterable
    {
        return $this->object->getReasons();
    }

    public function getDifferences(): BeneficiaryCompare
    {
        return new BeneficiaryCompare(
            $this->importLineFactory->create($this->object->getQueue(), $this->object->getMemberIndex()),
            $this->object->getBeneficiary(),
            $this->object
        );
    }

    public function getOriginFullName(): string
    {
        $person = $this->object->getBeneficiary()->getPerson();
        if (!empty($person->getLocalFamilyName()) || !empty($person->getLocalGivenName())) {
            return $person->getLocalGivenName() . ' ' . $person->getLocalFamilyName();
        } else {
            return $person->getEnGivenName() . ' ' . $person->getEnFamilyName();
        }
    }

    public function getDuplicityFullName(): string
    {
        $importLine = $this->importLineFactory->create($this->object->getQueue(), $this->object->getMemberIndex());

        if (!empty($importLine->localFamilyName) || !empty($importLine->localGivenName)) {
            return $importLine->localGivenName . ' ' . $importLine->localFamilyName;
        } else {
            return $importLine->englishGivenName . ' ' . $importLine->englishFamilyName;
        }
    }
}
