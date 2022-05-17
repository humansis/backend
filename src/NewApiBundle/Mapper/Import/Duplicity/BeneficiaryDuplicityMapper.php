<?php declare(strict_types=1);

namespace NewApiBundle\Mapper\Import\Duplicity;

use BeneficiaryBundle\Entity\Beneficiary;
use NewApiBundle\Component\Import\BeneficiaryFactory;
use NewApiBundle\Component\Import\CellParameters;
use NewApiBundle\Component\Import\Integrity\ImportLineFactory;
use NewApiBundle\Component\Import\ValueObject\BeneficiaryCompare;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Serializer\MapperInterface;

class BeneficiaryDuplicityMapper implements MapperInterface
{
    /** @var ImportBeneficiaryDuplicity */
    private $object;
    /** @var ImportLineFactory */
    private $importLineFactory;
    /** @var BeneficiaryFactory */
    private $beneficiaryFactory;

    /**
     * @param ImportLineFactory  $importLineFactory
     * @param BeneficiaryFactory $beneficiaryFactory
     */
    public function __construct(ImportLineFactory $importLineFactory, BeneficiaryFactory $beneficiaryFactory)
    {
        $this->importLineFactory = $importLineFactory;
        $this->beneficiaryFactory = $beneficiaryFactory;
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

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.ImportBeneficiaryDuplicity::class.', '.get_class($object).' given.');
    }

    public function getReasons(): iterable
    {
        return $this->object->getReasons();
    }

    public function getDifferences(): BeneficiaryCompare
    {
        $importLine = $this->importLineFactory->create($this->object->getQueue(), $this->object->getMemberIndex());
        $importedBeneficiary = $this->beneficiaryFactory->create($importLine);
        return $importedBeneficiary->compare($this->object->getBeneficiary());
    }

    public function getOriginFullName(): string
    {
        $person = $this->object->getBeneficiary()->getPerson();
        if (!empty($person->getLocalFamilyName()) || !empty($person->getLocalGivenName())) {
            return $person->getLocalGivenName().' '.$person->getLocalFamilyName();
        } else {
            return $person->getEnGivenName().' '.$person->getEnFamilyName();
        }
    }

    public function getDuplicityFullName(): string
    {
        $importLine = $this->importLineFactory->create($this->object->getQueue(), $this->object->getMemberIndex());

        if (!empty($importLine->localFamilyName) || !empty($importLine->localGivenName)) {
            return $importLine->localGivenName.' '.$importLine->localFamilyName;
        } else {
            return $importLine->englishGivenName.' '.$importLine->englishFamilyName;
        }
    }

}
