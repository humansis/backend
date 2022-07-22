<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\HouseholdLocation;
use BeneficiaryBundle\Entity\Referral;
use CommonBundle\Pagination\Paginator;
use NewApiBundle\Component\Codelist\CodeItem;
use NewApiBundle\Component\Codelist\CodeLists;
use NewApiBundle\Enum\Domain;
use NewApiBundle\Enum\HouseholdAssets;
use NewApiBundle\Enum\HouseholdShelterStatus;
use NewApiBundle\Enum\HouseholdSupportReceivedType;
use ProjectBundle\Enum\Livelihood;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Cache(expires="+5 days", public=true)
 */
class HouseholdCodelistController extends AbstractController
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    
    /**
     * @Rest\Get("/web-app/v1/households/livelihoods")
     *
     * @return JsonResponse
     */
    public function getLivelihoods(): JsonResponse
    {
        $data = CodeLists::mapEnum(Livelihood::values(), $this->translator, Domain::ENUMS);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/households/assets")
     *
     * @return JsonResponse
     */
    public function getAssets(): JsonResponse
    {
        $data = CodeLists::mapEnum(HouseholdAssets::values(), $this->translator);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/households/support-received-types")
     *
     * @return JsonResponse
     */
    public function supportReceivedTypes(): JsonResponse
    {
        $data = CodeLists::mapEnum(HouseholdSupportReceivedType::values(), $this->translator);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/households/shelter-statuses")
     *
     * @return JsonResponse
     */
    public function getShelterStatuses(): JsonResponse
    {
        $data = CodeLists::mapEnum(HouseholdShelterStatus::values(), $this->translator);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/households/locations/types")
     *
     * @return JsonResponse
     */
    public function getLocationTypes(): JsonResponse
    {
        $data = CodeLists::mapArray(HouseholdLocation::LOCATION_TYPES, $this->translator, Domain::ENUMS);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/households/referrals/types")
     *
     * @return JsonResponse
     */
    public function referralTypes(): JsonResponse
    {
        $data = CodeLists::mapArray(Referral::REFERRALTYPES, $this->translator, Domain::SECTORS);

        return $this->json(new Paginator($data));
    }
}
