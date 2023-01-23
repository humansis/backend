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
    private ?\Entity\ImportBeneficiaryDuplicity $object = null;

    public function __construct(private readonly ImportLineFactory $importLineFactory)
    {
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

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . ImportBeneficiaryDuplicity::class . ', ' . $object::class . ' given.'
        );
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

    public function getGivenName(): string
    {
        $person = $this->object->getBeneficiary()->getPerson();
        if (!empty($person->getLocalGivenName())) {
            return $person->getLocalGivenName();
        } else {
            return $person->getEnGivenName();
        }
    }

    public function getFamilyName(): string
    {
        $person = $this->object->getBeneficiary()->getPerson();
        if (!empty($person->getLocalFamilyName())) {
            return $person->getLocalFamilyName();
        } else {
            return $person->getEnFamilyName();
        }
    }

}
