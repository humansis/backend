<?php

namespace VoucherBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Entity\Logs;
use DistributionBundle\Entity\DistributionBeneficiary;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\Product;
use VoucherBundle\Entity\Voucher;
use Psr\Container\ContainerInterface;

class ProductService
{

  /** @var EntityManagerInterface $em */
  private $em;

  /** @var ValidatorInterface $validator */
  private $validator;

  /** @var ContainerInterface $container */
  private $container;

  /**
   * UserService constructor.
   * @param EntityManagerInterface $entityManager
   * @param ValidatorInterface $validator
   * @param ContainerInterface $container
   */
  public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, ContainerInterface $container)
  {
    $this->em = $entityManager;
    $this->validator = $validator;
    $this->container = $container;
  }


    /**
     * Creates a new Product entity
     *
     * @param array $productData
     * @return mixed
     * @throws \Exception
     */
    public function create(array $productData)
    {

        try {
            $product = new Product();

            $product->setImage($productData['image'])
              ->setName($productData['name'])
              ->setUnit($productData['unit'])
              ->setArchived(false);

            $this->em->persist($product);
            $this->em->flush();
        }
        catch (\Exception $e) {
            throw new \Exception('Error while creating a product' . $e->getMessage());
        }

        return $product;
    }

    /**
     * Returns all the products
     *
     * @return array
     */
    public function findAll() {
        return $this->em->getRepository(Product::class)->findBy(['archived' => false]);
    }

    /**
     * Updates a product according to the $productData
     *
     * @param Product $product
     * @param array $productData
     * @return Product
     */
    public function update(Product $product, array $productData) {
        $product->setUnit($productData['unit'])
            ->setImage($productData['image']);

        $this->em->merge($product);
        $this->em->flush();

        return $product;
    }

    /**
     * Archives a product
     *
     * @param Product $product
     * @return string
     */
    public function archive(Product $product) {
        $product->setArchived(true);

        $this->em->merge($product);
        $this->em->flush();

        return "Product suppressed";
    }
}
