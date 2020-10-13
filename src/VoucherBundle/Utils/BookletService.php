<?php

namespace VoucherBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Entity\DistributionData;
use Doctrine\ORM\EntityManagerInterface;
use ProjectBundle\Entity\Project;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\Voucher;
use CommonBundle\InputType;

class BookletService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var ContainerInterface $container */
    private $container;

    /** @var EventDispatcherInterface $eventDispatcher */
    private $eventDispatcher;

    /**
     * UserService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface $validator
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, ContainerInterface $container, EventDispatcherInterface $eventDispatcher)
    {
        $this->em = $entityManager;
        $this->validator = $validator;
        $this->container = $container;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Find one booklet by code
     *
     * @param string $code
     * @return Booklet
     */

    public function getOne(string $code)
    {
        return $this->em->getRepository(Booklet::class)->findOneBy(['code' => $code]);
    }

    /**
     * Create new booklets as a background task.
     * Returns the last booklet id currently in the database and the number of booklets to create.
     *
     * @param string $country
     * @param array $bookletData
     * @return int
     */
    public function backgroundCreate($country, array $bookletData)
    {
        $this->container->get('voucher.voucher_service')->cleanUp();

        $this->eventDispatcher->addListener(KernelEvents::TERMINATE, function ($event) use ($country, $bookletData) {
            try {
                $this->create($country, $bookletData);
            } catch (\Exception $e) {
                $this->container->get('logger')->error($e);
                $this->container->get('voucher.voucher_service')->cleanUp();
            }
        });

        return ["lastBooklet" => $this->getLastId(), "expectedNumber" => $bookletData['number_booklets']];
    }

    /**
     * Creates a new Booklet entity
     *
     * @param array $bookletData
     * @return mixed
     * @throws \Exception
     */
    public function create($countryISO3, array $bookletData)
    {
        $bookletBatch = $this->getBookletBatch();
        $currentBatch = $bookletBatch;
        $lastVoucherId = $this->container->get('voucher.voucher_service')->getLastId();
        for ($x = 0; $x < $bookletData['number_booklets']; $x++) {
            // Create booklet
            try {
                $booklet = new Booklet();
                $booklet
                    ->setNumberVouchers($bookletData['number_vouchers'])
                    ->setCurrency($bookletData['currency'])
                    ->setStatus(Booklet::UNASSIGNED)
                    ->setCountryISO3($countryISO3);

                $code = null;
                if (array_key_exists('project_id', $bookletData) && !empty($bookletData['project_id'])) {
                    $project = $this->em->getRepository(\ProjectBundle\Entity\Project::class)->find($bookletData['project_id']);
                    $booklet->setProject($project);

                    $code = $this->generateCode($countryISO3, $project);
                } else {
                    $code = $this->generateCodeDeprecated($bookletData, $currentBatch, $bookletBatch);
                }

                $booklet->setCode($code);

                if (array_key_exists('password', $bookletData) && !empty($bookletData['password'])) {
                    $booklet->setPassword($bookletData['password']);
                }

                $this->em->persist($booklet);
                $this->em->flush();

                $currentBatch++;
            } catch (\Exception $e) {
                throw new \Exception('Error creating Booklet ' . $e->getMessage() . ' ' . $e->getLine());
            }

            // Create vouchers
            try {
                $voucherData = [
                    'number_vouchers' => $bookletData['number_vouchers'],
                    'bookletCode' => $code,
                    'currency' => $bookletData['currency'],
                    'booklet' => $booklet,
                    'values' => $bookletData['individual_values'],
                    'lastId' => $lastVoucherId
                ];

                $this->container->get('voucher.voucher_service')->create($voucherData, false);
                $lastVoucherId += $bookletData['number_vouchers'];
            } catch (\Exception $e) {
                throw $e;
            }

            if ($x % 10 === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }
        $this->em->flush();
        $this->em->clear();

        return $booklet;
    }

    /**
     * Get the last inserted ID in the Booklet table
     *
     * @return int
     */
    public function getLastId()
    {
        $lastBooklet = $this->em->getRepository(Booklet::class)->findBy([], ['id' => 'DESC'], 1);

        return $lastBooklet ? $lastBooklet[0]->getId() : 0;
    }

    /**
     * Get the number of insterted booklets in a country since an ID.
     *
     * @param string $country
     * @param int $lastId
     *
     * @return int
     */
    public function getNumberOfInsertedBooklets(string $country, int $lastId)
    {
        $newBooklets = $this->em->getRepository(Booklet::class)->getInsertedBooklets($country, $lastId);
        if (!empty($newBooklets)) {
            return count($newBooklets);
        }
        return 0;
    }

    /**
     * Returns the index of the next booklet to be inserted in the database
     *
     * @return int
     */
    public function getBookletBatch()
    {
        $lastBooklet = $this->em->getRepository(Booklet::class)->findBy([], ['id' => 'DESC'], 1);
        if ($lastBooklet) {
            $bookletBatch = $lastBooklet[0]->getId() + 1;
            return $bookletBatch;
        } else {
            return 1;
        }
    }

    /**
     * Generates a random code for a booklet
     *
     * @param array $bookletData
     * @param int $currentBatch
     * @param int $bookletBatch
     * @return string
     * @deprecated Use generateCode() instead.
     */
    private function generateCodeDeprecated(array $bookletData, int $currentBatch, int $bookletBatch)
    {
        // randomCode*bookletBatchNumber-lastBatchNumber-currentBooklet
        $lastBatchNumber = $bookletBatch + ($bookletData['number_booklets'] - 1);
        $fullCode = $bookletBatch . '-' . $lastBatchNumber . '-' . $currentBatch;

        return $fullCode;
    }

    /**
     * Generates a random code for a booklet
     *
     * @param string $countryCode
     * @param \ProjectBundle\Entity\Project $project
     * @return string
     */
    protected function generateCode(string $countryCode, \ProjectBundle\Entity\Project $project)
    {
        $prefix = $countryCode . '_' . $project->getName() . '_' . date('d-m-Y') . '_batch';
        $count = 0;

        $booklet = $this->em->getRepository(Booklet::class)->findMaxByCodePrefix($prefix);
        if ($booklet) {
            $count = (int) substr($booklet->getCode(), -6);
        }
        return sprintf('%s%06d', $prefix, ++$count);
    }

    /**
     * Get all the non-deactivated booklets from the database
     *
     * @return array
     */
    public function findAll($countryISO3)
    {
        return $this->em->getRepository(Booklet::class)->getActiveBooklets($countryISO3);
    }

    /**
     * Get all the deactivated booklets from the database
     *
     * @return array
     */
    public function findDeactivated()
    {
        return $this->em->getRepository(Booklet::class)->findBy(['status' => Booklet::DEACTIVATED]);
    }

    /**
     * Get all the protected booklets from the database
     *
     * @return array
     */
    public function findProtected()
    {
        return $this->em->getRepository(Booklet::class)->getProtectedBooklets();
    }

    /**
     * Updates a booklet
     *
     * @param Booklet $booklet
     * @param array $bookletData
     * @return Booklet
     * @throws \Exception
     */
    public function update(Booklet $booklet, array $bookletData)
    {
        try {
            $booklet->setCurrency($bookletData['currency']);
            $initialNumberVouchers = $booklet->getNumberVouchers();

            $newNumberVouchers = $bookletData['number_vouchers'];
            $booklet->setNumberVouchers($newNumberVouchers);

            $vouchersToAdd = (int) $bookletData['number_vouchers'] - $initialNumberVouchers;

            // Create vouchers without default value and no password
            if ($vouchersToAdd > 0) {
                try {
                    $values = array_fill(0, $vouchersToAdd, 1);
                    $voucherData = [
                        'number_vouchers' => $vouchersToAdd,
                        'bookletCode' => $booklet->getCode(),
                        'currency' => $bookletData['currency'],
                        'booklet' => $booklet,
                        'values' => $values,
                    ];

                    $this->container->get('voucher.voucher_service')->create($voucherData);
                } catch (\Exception $e) {
                    throw new \Exception('Error creating vouchers');
                }
            } elseif ($vouchersToAdd < 0) {
                $vouchersToRemove = -$vouchersToAdd;
                $vouchers = $this->em->getRepository(Voucher::class)->findBy(['booklet' => $booklet->getId()]);
                foreach ($vouchers as $voucher) {
                    if ($vouchersToRemove > 0) {
                        $this->container->get('voucher.voucher_service')->deleteOneFromDatabase($voucher);
                        $vouchersToRemove -= 1;
                    }
                }
            }

            if (array_key_exists('password', $bookletData) && !empty($bookletData['password'])) {
                $booklet->setPassword($bookletData['password']);
            }
            $this->em->merge($booklet);

            $vouchers = $this->em->getRepository(Voucher::class)->findBy(['booklet' => $booklet->getId()]);
            $values = array_key_exists('individual_values', $bookletData) ? $bookletData['individual_values'] : [];
            foreach ($vouchers as $index => $voucher) {
                $password = array_key_exists('password', $bookletData) ? $bookletData['password'] : null;
                $value = $values[$index] ?: null;
                $this->updateVoucherCode($voucher, $password, $value, $bookletData['currency']);
            }

            $this->em->flush();
        } catch (\Exception $e) {
            throw new \Exception('Error updating Booklet');
        }
        return $booklet;
    }

    public function updateVoucherCode(Voucher $voucher, ?string $password = '', ?string $value = '', ?string $currency = '')
    {
        $qrCode = $voucher->getCode();
        // To know if we need to add a new password or replace an existant one
        preg_match('/^([A-Z]+)(\d+)\*[\d]+-[\d]+-[\d]+-[\d]+-([\dA-Z=+-\/]+)$/i', $qrCode, $matches);

        if ($matches === null || count($matches) < 3) {
            preg_match('/^([A-Z]+)(\d+)\*[\d]+-[\d]+-[\d]+-[\d]+$/i', $qrCode, $matches);
            if (!empty($password)) {
                $qrCode .= '-' . $password;
            }
        } else {
            if (!empty($password)) {
                $qrCode = str_replace($matches[3], $password, $qrCode);
            }
        }

        if (!empty($value)) {
            $voucher->setValue($value);
            $oldValuePos = strpos($qrCode, $matches[2]);
            $qrCode = substr_replace($qrCode, $value, $oldValuePos, strlen($matches[2]));
        }
        if (!empty($currency)) {
            $oldCurrencyPos = strpos($qrCode, $matches[1]);
            $qrCode = substr_replace($qrCode, $currency, $oldCurrencyPos, strlen($matches[1]));
        }
        $voucher->setCode($qrCode);

        $this->em->merge($voucher);
    }


    /**
     * Deactivate a booklet
     *
     * @param Booklet $booklet
     * @return string
     */
    public function deactivate(Booklet $booklet)
    {
        $booklet->setStatus(Booklet::DEACTIVATED);

        $this->em->merge($booklet);
        $this->em->flush();

        return "Booklet has been deactivated";
    }

    /**
     * Deactivate many booklet
     *
     * @param string[] $bookletCodes
     * @return string
     */
    public function deactivateMany(?array $bookletCodes = [])
    {
        foreach ($bookletCodes as $bookletCode) {
            $booklet = $this->em->getRepository(Booklet::class)->findOneByCode($bookletCode);
            $booklet->setStatus(Booklet::DEACTIVATED);
            $this->em->merge($booklet);
        }

        $this->em->flush();

        return "Booklets have been deactivated";
    }


    /**
     * Update the password of the booklet
     *
     * @param Booklet $booklet
     * @param int $code
     * @return string
     * @throws \Exception
     *
     */
    public function updatePassword(Booklet $booklet, $password)
    {
        if ($booklet->getStatus() === Booklet::DEACTIVATED || $booklet->getStatus() === Booklet::USED) {
            throw new \Exception("This booklet has already been used and is actually deactivated");
        }

        $booklet->setPassword($password);
        $vouchers = $this->em->getRepository(Voucher::class)->findBy(['booklet' => $booklet->getId()]);
        foreach ($vouchers as $voucher) {
            $this->updateVoucherCode($voucher, $password, null, null);
        }
        $this->em->merge($booklet);
        $this->em->flush();

        return "Password has been set";
    }

    /**
     * Assign the booklet to a beneficiary
     *
     * @param Booklet $booklet
     * @param Beneficiary $beneficiary
     * @param DistributionData $distributionData
     * @return string
     * @throws \Exception
     *
     */
    public function assign(Booklet $booklet, DistributionData $distributionData, Beneficiary $beneficiary)
    {
        if ($booklet->getStatus() === Booklet::DEACTIVATED || $booklet->getStatus() === Booklet::USED || $booklet->getStatus() === Booklet::DISTRIBUTED) {
            throw new \Exception("This booklet has already been distributed, used or is actually deactivated");
        }

        $distributionBeneficiary = $this->em->getRepository(DistributionBeneficiary::class)->findOneBy(
            ['beneficiary' => $beneficiary, "distributionData" => $distributionData]
        );
        $booklet->setDistributionBeneficiary($distributionBeneficiary)
            ->setStatus(Booklet::DISTRIBUTED);
        $this->em->merge($booklet);

        $beneficiariesWithoutBooklets = $this->em->getRepository(DistributionBeneficiary::class)->countWithoutBooklet($distributionData);

        if ($beneficiariesWithoutBooklets === '1') {
            $distributionData->setCompleted(true);
            $this->em->merge($distributionData);
        }

        $this->em->flush();

        return "Booklet successfully assigned to the beneficiary";
    }

    // =============== DELETE 1 BOOKLET AND ITS VOUCHERS FROM DATABASE ===============

    /**
     * Permanently delete the record from the database
     *
     * @param Booklet $booklet
     * @param bool $removeBooklet
     * @return bool
     * @throws \Exception
     */
    public function deleteBookletFromDatabase(Booklet $booklet, bool $removeBooklet = true)
    {
        // === check if booklet has any vouchers ===
        $vouchers = $this->em->getRepository(Voucher::class)->findBy(['booklet' => $booklet]);
        if ($removeBooklet && !$vouchers) {
            try {
                // === if no vouchers then delete ===
                $this->em->remove($booklet);
                $this->em->flush();
            } catch (\Exception $exception) {
                throw new \Exception('Unable to delete Booklet');
            }
        } elseif ($removeBooklet && $vouchers) {
            try {
                // === if there are vouchers then delete those that are not used ===
                $this->container->get('voucher.voucher_service')->deleteBatchVouchers($booklet);
                $this->em->remove($booklet);
                $this->em->flush();
            } catch (\Exception $exception) {
                throw new \Exception('This booklet still contains potentially used vouchers.');
            }
        } else {
            return false;
        }
        return true;
    }

    public function printMany(array $bookletIds)
    {
        $booklets = [];
        foreach ($bookletIds as $bookletId) {
            $booklet = $this->em->getRepository(Booklet::class)->find($bookletId);
            $booklets[] = $booklet;
        }

        try {
            return $this->generatePdf($booklets);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function generatePdf(array $booklets)
    {
        try {
            $voucherHtmlSeparation = '<p class="next-voucher"></p>';
            $html = $this->getPdfHtml($booklets[0], $voucherHtmlSeparation);

            foreach ($booklets as $booklet) {
                if ($booklet !== $booklets[0]) {
                    $bookletHtml = $this->getPdfHtml($booklet, $voucherHtmlSeparation);
                    preg_match('/<main>([\s\S]*)<\/main>/', $bookletHtml, $matches);
                    $bookletInnerHtml = '<p style="page-break-before: always">' . $matches[1];
                    $pos = strrpos($html, $voucherHtmlSeparation);
                    $html = substr_replace($html, $bookletInnerHtml, $pos, strlen($voucherHtmlSeparation));
                }
            }

            $response = $this->container->get('pdf_service')->printPdf($html, 'portrait', 'booklets');

            return $response;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    public function getPdfHtml(Booklet $booklet, string $voucherHtmlSeparation)
    {
        $name = $booklet->getDistributionBeneficiary() ?
            $booklet->getDistributionBeneficiary()->getBeneficiary()->getLocalFamilyName() :
            '_______';
        $currency = $booklet->getCurrency();
        $bookletQrCode = $booklet->getCode();
        $vouchers = $booklet->getVouchers();
        $totalValue = 0;
        $numberVouchers = $booklet->getNumberVouchers();

        foreach ($vouchers as $voucher) {
            $totalValue += $voucher->getValue();
        }

        $bookletHtml = $this->container->get('templating')->render(
            '@Voucher/Pdf/booklet.html.twig',
            array_merge(
                array(
                    'name' => $name,
                    'value' => $totalValue,
                    'currency' => $currency,
                    'qrCodeLink' => 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . $bookletQrCode,
                    'numberVouchers' => $numberVouchers
                ),
                $this->container->get('pdf_service')->getInformationStyle()
            )
        );

        $pageBreak = true;

        foreach ($vouchers as $voucher) {
            $voucherQrCode = $voucher->getCode();

            $voucherHtml = $this->container->get('templating')->render(
                '@Voucher/Pdf/voucher.html.twig',
                array(
                    'name' => $name,
                    'value' => $voucher->getValue(),
                    'currency' => $currency,
                    'qrCodeLink' => 'https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=' . $voucherQrCode
                )
            );

            if ($pageBreak === true) {
                $voucherHtml = '<p style="page-break-before: always">' . $voucherHtml;
            } else {
                $voucherHtml = '<div><img class="scissors" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAAlAAAAJQBeb8N7wAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAE8SURBVDiNrdQ9L0RBFAbgZzchmwgJjX9gSzo/QKJD6zuhRqNUiERJR4coRdQ0oqSjFUGv8ZGQWKxV7Gzc3J29NrJvcjK5c8+8cz7mvLQYubDmMY1RdOIG27hugqMdHXiqbfTgApWUlbCUQVTECT6D/z0mYTdB8ohLfIfvMgYiZMN4iQRxJoRawSt6w4GVhNNaimwOHxGyQxTyKeecxsgF8j20pf5tYhzvsJNK+SqRcgXrgWA/EtUXFtM3d+M84py028jeG8YapZPHjGodTsVrlLQHDGaUpw5DeM6Iti8rshiymtMU8pjFkRak3GhS/t2UrEn517NpdlJqDzuWwUYoW11TYs2oJNZVzKsKQhLLOECB7Ekpoz9ySaY4NJqUEhYiZDUUcexXvu4wkRTYKYygS1Vgt8L6F+oEtqX4AeYWq/jZKMK/AAAAAElFTkSuQmCC" /></div><hr class="separation">' . $voucherHtml;
            }

            $pageBreak = !$pageBreak;

            $pos = strrpos($bookletHtml, $voucherHtmlSeparation);
            $bookletHtml = substr_replace($bookletHtml, $voucherHtml, $pos, strlen($voucherHtmlSeparation));
        }

        return $bookletHtml;
    }

    /**
     * @param DistributionData $distributionData
     * @param string $type
     * @return mixed
     */
    public function exportVouchersDistributionToCsv(DistributionData $distributionData, string $type)
    {
        $distributionBeneficiaries = $this->em->getRepository(DistributionBeneficiary::class)
            ->findByDistributionData($distributionData);

        $beneficiaries = array();
        $exportableTable = array();
        foreach ($distributionBeneficiaries as $distributionBeneficiary) {
            $beneficiary = $distributionBeneficiary->getBeneficiary();
            $booklets = $distributionBeneficiary->getBooklets();
            $transactionBooklet = null;
            if (count($booklets) > 0) {
                foreach ($booklets as $booklet) {
                    if ($booklet->getStatus() !== 3) {
                        $transactionBooklet = $booklet;
                    }
                }
                if ($transactionBooklet === null) {
                    $transactionBooklet = $booklets[0];
                }
            }

            $commonFields = $beneficiary->getCommonExportFields();

            $products = [];
            if ($transactionBooklet) {
                /** @var Voucher $voucher */
                foreach ($transactionBooklet->getVouchers() as $voucher) {
                    foreach ($voucher->getRecords() as $record) {
                        array_push($products, $record->getProduct()->getName());
                    }
                }
            }
            $products = implode(', ', array_unique($products));

            array_push(
                $exportableTable,
                array_merge($commonFields, array(
                    "Booklet" => $transactionBooklet ? $transactionBooklet->getCode() : null,
                    "Status" => $transactionBooklet ? $transactionBooklet->getStatus() : null,
                    "Value" => $transactionBooklet ? $transactionBooklet->getTotalValue() . ' ' . $transactionBooklet->getCurrency() : null,
                    "Used At" => $transactionBooklet ? $transactionBooklet->getUsedAt() : null,
                    "Purchased items" => $products,
                    "Removed" => $distributionBeneficiary->getRemoved() ? 'Yes' : 'No',
                    "Justification for adding/removing" => $distributionBeneficiary->getJustification(),
                ))
            );
        }

        return $this->container->get('export_csv_service')->export($exportableTable, 'qrVouchers', $type);
    }

    /**
     * @param InputType\Country $countryISO3
     * @param InputType\DataTableType $filter
     * @return mixed
     */
    public function getAll(InputType\Country $countryISO3, InputType\DataTableType $filter)
    {
        $limitMinimum = $filter->pageIndex * $filter->pageSize;

        $booklets = $this->em->getRepository(Booklet::class)->getAllBy($countryISO3->getIso3(), $limitMinimum, $filter->pageSize, $filter->getSort(), $filter->getFilter());
        $length = $booklets[0];
        $booklets = $booklets[1];
        return [$length, $booklets];
    }
}
