<?php

namespace VoucherBundle\Annotation;

use JMS\Serializer\Annotation\Type as JMS_Type;

/**
 * Helper class for encapsulate definition of inputs for voucher/scanned request
 */
class VoucherScanned
{

    /**
     * @var int $id identifier of scanned voucher
     * @required
     * @JMS_Type("int")
     */
    public $id;

    /**
     * @var int $productId identifier of product
     * @JMS_Type("int")
     */
    public $productId;

    /**
     * @var int $vendorId identifier of vendor
     * @JMS_Type("int")
     */
    public $vendorId;

    /**
     * @var string|null $booklet booklet code
     * @JMS_Type("string")
     */
    public $booklet;

    /**
     * @var string|null $usedAt datetime of voucher was scanned
     * @JMS_Type("DateTime<'d-m-Y H:i:s'>")
     */
    public $usedAt;

    /**
     * @var float|null $value price of product
     * @JMS_Type("float")
     */
    public $value;

    /**
     * @var float|null $quantity quantity of product
     * @JMS_Type("float")
     */
    public $quantity;
}
