<?php

namespace Utils;

use Doctrine\ORM\EntityNotFoundException;
use Entity\AbstractBeneficiary;
use Entity\AssistanceBeneficiary;
use Entity\Assistance;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InputType\BookletBatchCreateInputType;
use Entity\Project;
use InvalidArgumentException;
use Twig\Environment;
use Entity\Booklet;
use Entity\Voucher;
use InputType;

class BookletService
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly BookletGenerator $generator, private readonly Environment $twig, private readonly VoucherService $voucherService, private readonly PdfService $pdfService)
    {
    }

    /**
     * Find one booklet by code
     *
     * @return Booklet
     */
    public function getOne(string $code)
    {
        return $this->em->getRepository(Booklet::class)->findOneBy(['code' => $code]);
    }

    /**
     * Creates a new Booklet entity
     *
     * @return mixed
     * @throws Exception
     * @deprecated
     */
    public function create($countryISO3, array $bookletData)
    {
        $inputType = new BookletBatchCreateInputType();
        $inputType->setQuantityOfBooklets($bookletData['number_booklets']);
        $inputType->setQuantityOfVouchers($bookletData['number_vouchers']);
        $inputType->setProjectId($bookletData['project_id']);
        $inputType->setValues($bookletData['individual_values']);
        $inputType->setCurrency($bookletData['currency']);
        $inputType->setPassword($bookletData['password'] ?? null);
        $inputType->setIso3($countryISO3);

        $this->createBooklets($inputType);

        return $this->em->getRepository(Booklet::class)->findOneBy([], ['id' => 'DESC']);
    }

    public function createBooklets(BookletBatchCreateInputType $inputType)
    {
        $project = $this->em->getRepository(Project::class)->find($inputType->getProjectId());
        if (!$project) {
            throw new EntityNotFoundException('Project #' . $inputType->getProjectId() . ' does not exists');
        }

        $this->generator->generate(
            $project,
            $inputType->getIso3(),
            $inputType->getQuantityOfBooklets(),
            $inputType->getQuantityOfVouchers(),
            $inputType->getCurrency(),
            $inputType->getValues(),
            $inputType->getPassword()
        );
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
     * @return Booklet
     * @throws Exception
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

                    $this->voucherService->create($voucherData);
                } catch (Exception) {
                    throw new Exception('Error creating vouchers');
                }
            } elseif ($vouchersToAdd < 0) {
                $vouchersToRemove = -$vouchersToAdd;
                $vouchers = $this->em->getRepository(Voucher::class)->findBy(['booklet' => $booklet->getId()]);
                foreach ($vouchers as $voucher) {
                    if ($vouchersToRemove > 0) {
                        $this->voucherService->deleteOneFromDatabase($voucher);
                        $vouchersToRemove -= 1;
                    }
                }
            }

            if (array_key_exists('password', $bookletData) && !empty($bookletData['password'])) {
                $booklet->setPassword($bookletData['password']);
            }
            $this->em->persist($booklet);

            $vouchers = $this->em->getRepository(Voucher::class)->findBy(['booklet' => $booklet->getId()]);
            $values = array_key_exists('individual_values', $bookletData) ? $bookletData['individual_values'] : [];
            foreach ($vouchers as $index => $voucher) {
                $password = array_key_exists('password', $bookletData) ? $bookletData['password'] : null;
                $value = $values[$index] ?? null;
                $this->updateVoucherCode($voucher, $password, $value, $bookletData['currency']);
            }

            $this->em->flush();
        } catch (Exception) {
            throw new Exception('Error updating Booklet');
        }

        return $booklet;
    }

    public function updateVoucherCode(
        Voucher $voucher,
        ?string $password = '',
        ?string $value = '',
        ?string $currency = ''
    ) {
        $qrCode = $voucher->getCode();
        // To know if we need to add a new password or replace an existant one
        preg_match(
            '/^([A-Z]+)(\d+)\*[^_]+_[^_]+_[^_]+_((batch)|(booklet))[\d]+-[\d]+(-[\dA-Z=+-\/]+)$/i',
            $qrCode,
            $matches
        );

        if ($matches === null || count($matches) < 3) {
            preg_match('/^([A-Z]+)(\d+)\*[^_]+_[^_]+_[^_]+_((batch)|(booklet))[\d]+-[\d]+$/i', $qrCode, $matches);
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
            $oldValuePos = strpos($qrCode, (string) $matches[2]);
            $qrCode = substr_replace($qrCode, $value, $oldValuePos, strlen((string) $matches[2]));
        }
        if (!empty($currency)) {
            $oldCurrencyPos = strpos($qrCode, (string) $matches[1]);
            $qrCode = substr_replace($qrCode, $currency, $oldCurrencyPos, strlen((string) $matches[1]));
        }
        $voucher->setCode($qrCode);

        $this->em->persist($voucher);
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
            $this->em->persist($booklet);
        }

        $this->em->flush();

        return "Booklets have been deactivated";
    }

    /**
     * Assign the booklet to a beneficiary
     *
     * @return string
     * @throws Exception
     *
     */
    public function assign(Booklet $booklet, Assistance $assistance, AbstractBeneficiary $abstractBeneficiary)
    {
        if (
            $booklet->getStatus() === Booklet::DEACTIVATED || $booklet->getStatus(
            ) === Booklet::USED || $booklet->getStatus() === Booklet::DISTRIBUTED
        ) {
            throw new Exception("This booklet has already been distributed, used or is actually deactivated");
        }

        /** @var AssistanceBeneficiary|null $assistanceBeneficiary */
        $assistanceBeneficiary = $this->em->getRepository(AssistanceBeneficiary::class)->findOneBy(
            ['beneficiary' => $abstractBeneficiary, "assistance" => $assistance]
        );

        if (!$assistanceBeneficiary instanceof AssistanceBeneficiary) {
            throw new InvalidArgumentException(
                'Beneficiary with id ' . $abstractBeneficiary->getId(
                ) . ' does not belong to assistance with id ' . $assistance->getId()
            );
        }

        $booklet->setAssistanceBeneficiary($assistanceBeneficiary)
            ->setStatus(Booklet::DISTRIBUTED);
        $this->em->persist($booklet);

        $this->em->flush();

        return "Booklet successfully assigned to the beneficiary";
    }

    // =============== DELETE 1 BOOKLET AND ITS VOUCHERS FROM DATABASE ===============
    /**
     * Permanently delete the record from the database
     *
     * @return bool
     * @throws Exception
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
            } catch (Exception) {
                throw new Exception('Unable to delete Booklet');
            }
        } elseif ($removeBooklet && $vouchers) {
            try {
                // === if there are vouchers then delete those that are not used ===
                $this->voucherService->deleteBatchVouchers($booklet);
                $this->em->remove($booklet);
                $this->em->flush();
            } catch (Exception) {
                throw new Exception('This booklet still contains potentially used vouchers.');
            }
        } else {
            return false;
        }

        return true;
    }

    public function generatePdf(array $booklets)
    {
        try {
            $voucherHtmlSeparation = '<p class="next-voucher"></p>';
            $html = $this->getPdfHtml($booklets[0], $voucherHtmlSeparation);

            foreach ($booklets as $booklet) {
                if ($booklet !== $booklets[0]) {
                    $bookletHtml = $this->getPdfHtml($booklet, $voucherHtmlSeparation);
                    preg_match('/<main>([\s\S]*)<\/main>/', (string) $bookletHtml, $matches);
                    $bookletInnerHtml = '<p style="page-break-before: always">' . $matches[1];
                    $pos = strrpos((string) $html, $voucherHtmlSeparation);
                    $html = substr_replace($html, $bookletInnerHtml, $pos, strlen($voucherHtmlSeparation));
                }
            }

            $response = $this->pdfService->printPdf($html, 'portrait', 'booklets');

            return $response;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    public function getPdfHtml(Booklet $booklet, string $voucherHtmlSeparation)
    {
        $name = $booklet->getAssistanceBeneficiary() ?
            $booklet->getAssistanceBeneficiary()->getBeneficiary()->getPerson()->getLocalFamilyName() :
            '_______';
        $currency = $booklet->getCurrency();
        $bookletQrCode = $booklet->getCode();
        $vouchers = $booklet->getVouchers();
        $totalValue = 0;
        $numberVouchers = $booklet->getNumberVouchers();

        foreach ($vouchers as $voucher) {
            $totalValue += $voucher->getValue();
        }

        $bookletHtml = $this->twig->render(
            'Pdf/booklet.html.twig',
            array_merge(
                [
                    'name' => $name,
                    'value' => $totalValue,
                    'currency' => $currency,
                    'qrCodeLink' => 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . $bookletQrCode,
                    'numberVouchers' => $numberVouchers,
                ],
                $this->pdfService->getInformationStyle()
            )
        );

        $pageBreak = true;

        foreach ($vouchers as $voucher) {
            $voucherQrCode = $voucher->getCode();

            $voucherHtml = $this->twig->render(
                'Pdf/voucher.html.twig',
                [
                    'name' => $name,
                    'value' => $voucher->getValue(),
                    'currency' => $currency,
                    'qrCodeLink' => 'https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=' . $voucherQrCode,
                ]
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
     * @return mixed
     */
    public function getAll(InputType\Country $countryISO3, InputType\DataTableType $filter)
    {
        $limitMinimum = $filter->pageIndex * $filter->pageSize;

        $booklets = $this->em->getRepository(Booklet::class)->getAllBy(
            $countryISO3->getIso3(),
            $limitMinimum,
            $filter->pageSize,
            $filter->getSort(),
            $filter->getFilter()
        );
        $length = $booklets[0];
        $booklets = $booklets[1];

        return [$length, $booklets];
    }
}
