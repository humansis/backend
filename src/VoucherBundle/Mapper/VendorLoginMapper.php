<?php declare(strict_types=1);

namespace VoucherBundle\Mapper;

use ArrayObject;
use Countable;
use InvalidArgumentException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use NewApiBundle\Serializer\MapperInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Serializer;
use VoucherBundle\Entity\Vendor;

class VendorLoginMapper implements MapperInterface
{
    /** @var Vendor */
    private $object;

    /**
     * @var JWTTokenManagerInterface
     */
    private $JWTTokenManager;

    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct(JWTTokenManagerInterface $JWTTokenManager, Serializer $serializer)
    {
        $this->JWTTokenManager = $JWTTokenManager;
        $this->serializer = $serializer;
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

    public function getUserId(): int
    {
        return $this->object->getUser()->getId();
    }

    public function getUsername(): string
    {
        return $this->object->getUser()->getUsername();
    }

    public function getToken(): string
    {
        return $this->JWTTokenManager->create($this->object->getUser());
    }

    /**
     * @return array|ArrayObject|bool|Countable|float|int|mixed|string|null
     * @throws ExceptionInterface
     */
    public function getLocation()
    {
        return $this->serializer->normalize($this->object->getLocation(), null, ['groups' => ['FullVendor']]);
    }
}
