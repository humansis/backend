<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard;

use ProjectBundle\Entity\Project;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Repository\SmartcardPurchaseRepository;

class SmartcardPurchaseService
{
    /** @var SmartcardPurchaseRepository */
    private $repository;

    public function __construct(SmartcardPurchaseRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getBy(Vendor $vendor, Project $project, string $currency)
    {
        $candidates = $this->repository->countPreliminaryInvoices($vendor);
        foreach ($candidates as $candidate) {
            if ($candidate->getProjectId() === $project->getId() && $candidate->getCurrency() === $currency) {
                return $this->repository->findBy(['id' => $candidate->getPurchasesIds()]);
            }
        }

        return [];
    }
}
