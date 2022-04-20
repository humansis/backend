<?php declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class CommonController extends AbstractOfflineAppController
{
    /**
     * @Rest\Get("/offline-app/v1/master-key")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @return JsonResponse
     */
    public function masterKeyOfflineApp(): JsonResponse
    {
        return $this->json([
            'MASTER_KEY' => $this->getParameter('mobile_app_master_key'),
            'APP_VERSION' => $this->getParameter('mobile_app_version'),
            'APP_ID' => $this->getParameter('mobile_app_id'),
        ]);
    }
}
