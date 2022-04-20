<?php declare(strict_types=1);

namespace NewApiBundle\Controller\VendorApp;

use BeneficiaryBundle\Entity\Beneficiary;
use DistributionBundle\Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use VoucherBundle\Utils\BookletService;

class BookletController extends AbstractVendorAppController
{
    /** @var BookletService */
    private $bookletService;

    /**
     * @param BookletService $bookletService
     */
    public function __construct(BookletService $bookletService)
    {
        $this->bookletService = $bookletService;
    }

    /**
     * Get booklets that have been deactivated
     *
     * @Rest\Get("/vendor-app/v1/deactivated-booklets")
     *
     * @return JsonResponse
     */
    public function deactivatedBooklets(): JsonResponse
    {
        try {
            $booklets = $this->bookletService->findDeactivated();
        } catch (\Exception $exception) {
            return new JsonResponse($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->json($booklets);
    }

    /**
     * Get booklets that are protected by a password
     *
     * @Rest\Get("/vendor-app/v1/protected-booklets")
     */
    public function getProtectedAction()
    {
        try {
            $booklets = $this->bookletService->findProtected();
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $bookletPasswords = [];

        foreach ($booklets as $booklet) {
            $bookletPasswords[] = [
                $booklet->getCode() => $booklet->getPassword()
            ];
        }

        return $this->json($bookletPasswords);
    }

    /**
     * Deactivate booklets
     * @Rest\Post("/vendor-app/v1/deactivate-booklets", name="deactivate_booklets")
     * @Security("is_granted('ROLE_USER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function deactivateBookletsAction(Request $request): Response
    {
        try {
            $data = $request->request->all();
            $bookletCodes = $data['bookletCodes'];
            $this->bookletService->deactivateMany($bookletCodes);
        } catch (\Exception $exception) {
            $this->container->get('logger')->error('exception', [$exception->getMessage()]);
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->json('Booklet successfully deactivated');
    }

    /**
     * Assign the booklet to a specific beneficiary.
     *
     * @Rest\Post("/offline-app/v1/booklets/assign/{distributionId}/{beneficiaryId}")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_ASSIGN')")
     * @ParamConverter("booklet", options={"mapping": {"bookletId": "code"}})
     * @ParamConverter("assistance", options={"mapping": {"distributionId": "id"}})
     * @ParamConverter("beneficiary", options={"mapping": {"beneficiaryId": "id"}})
     *
     * @param Request          $request
     * @param Assistance $assistance
     * @param Beneficiary      $beneficiary
     * @return Response
     */
    public function offlineAssignAction(Request $request, Assistance $assistance, Beneficiary $beneficiary)
    {
        $code = $request->request->get('code');
        $booklet = $this->bookletService->getOne($code);
        try {
            $return = $this->bookletService->assign($booklet, $assistance, $beneficiary);
        } catch (\Exception $exception) {
            $this->container->get('logger')->error('exception', [$exception->getMessage()]);
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(json_encode($return));
    }
}
