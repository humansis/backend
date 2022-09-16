<?php declare(strict_types=1);

namespace Mapper;

use InvalidArgumentException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Serializer\MapperInterface;
use Entity\Vendor;

class VendorLoginMapper implements MapperInterface
{
    /** @var Vendor */
    private $object;

    /**
     * @var JWTTokenManagerInterface
     */
    private $JWTTokenManager;

    public function __construct(JWTTokenManagerInterface $JWTTokenManager)
    {
        $this->JWTTokenManager = $JWTTokenManager;
    }

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Vendor &&
            isset($context[MapperInterface::VENDOR_APP]) && $context[MapperInterface::VENDOR_APP] === true &&
            isset($context['login']) && $context['login'] === true;
    }

    public function populate(object $object)
    {
        if ($object instanceof Vendor) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException('Invalid argument. It should be instance of '.Vendor::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getUser()->getId();
    }

    public function getVendorId(): int
    {
        return $this->object->getId();
    }

    public function getUsername(): string
    {
        return $this->object->getUser()->getUsername();
    }

    public function getToken(): string
    {
        return $this->JWTTokenManager->create($this->object->getUser());
    }

    public function getCountryISO3(): string
    {
        return $this->object->getLocation()->getCountryISO3();
    }
}
