<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use BeneficiaryBundle\Entity\Beneficiary;
use NewApiBundle\Component\Import\CellParameters;
use NewApiBundle\Component\Import\Integrity\ImportLineFactory;
use NewApiBundle\Component\Import\ValueObject\ImportBeneficiaryDuplicityCompare;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Serializer\MapperInterface;

class ImportBeneficiaryDuplicityMapper implements MapperInterface
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
    public function populate(object $object)
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

    public function getDifferences(): ImportBeneficiaryDuplicityCompare
    {
        return new ImportBeneficiaryDuplicityCompare(
            $this->importLineFactory->create($this->object->getQueue(), $this->object->getMemberIndex()),
            $this->object->getBeneficiary(),
            $this->object
        );
    }

    public function getOriginFullName(): string
    {
        $person = $this->object->getBeneficiary()->getPerson();
        if (!empty($person->getLocalFamilyName()) || !empty($person->getLocalGivenName())) {
            return $person->getLocalFamilyName().' '.$person->getLocalGivenName();
        } else {
            return $person->getEnFamilyName().' '.$person->getEnGivenName();
        }
    }

    public function getDuplicityFullName(): string
    {
        $person = $this->object->getBeneficiary()->getPerson();
        $userData = $this->object->getQueue()->getContent()[$this->object->getMemberIndex()];

        if (!empty($person->getLocalFamilyName()) || !empty($person->getLocalGivenName())) {
            return $userData['Local family name'][CellParameters::VALUE].' '.$userData['Local given name'][CellParameters::VALUE];
        } else {
            return $userData['English family name'][CellParameters::VALUE].' '.$userData['English given name'][CellParameters::VALUE];
        }
    }

}
