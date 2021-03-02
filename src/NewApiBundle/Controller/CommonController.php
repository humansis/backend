<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Household;
use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CommonController extends AbstractController
{
    /**
     * @Rest\Get("/summaries")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function summaries(Request $request): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $result = [];
        foreach ($request->query->get('code', []) as $code) {
            switch ($code) {
                case 'total_registrations':
                    $result[] = ['code' => $code, 'value' => $this->get('beneficiary.beneficiary_service')->countAll($countryIso3)];
                    break;
                case 'active_projects':
                    $result[] = ['code' => $code, 'value' => $this->get('project.project_service')->countActive($countryIso3)];
                    break;
                case 'enrolled_beneficiaries':
                    $result[] = ['code' => $code, 'value' => $this->getDoctrine()->getRepository(Household::class)->countUnarchivedByCountryProjects($countryIso3)];
                    break;
                case 'served_beneficiaries':
                    $result[] = ['code' => $code, 'value' => $this->get('beneficiary.beneficiary_service')->countAllServed($countryIso3)];
                    break;
                case 'completed_assistances':
                    $result[] = ['code' => $code, 'value' => $this->get('distribution.assistance_service')->countCompleted($countryIso3)];
                    break;
                default:
                    throw new BadRequestHttpException('Invalid query parameter code.'.$code);
            }
        }

        return $this->json(new Paginator($result));
    }

    /**
     * @Rest\Get("/icons")
     *
     * @return JsonResponse
     */
    public function icons(): JsonResponse
    {
        $data = [];

        foreach ($this->getParameter('icons_modality_types') as $key => $svg) {
            $data[] = ['key' => $key, 'svg' => $svg];
        }

        foreach ($this->getParameter('icons_sectors') as $key => $svg) {
            $data[] = ['key' => $key, 'svg' => $svg];
        }

        return $this->json($data);
    }

    /**
     * @Rest\Get("/languages")
     *
     * @return JsonResponse
     */
    public function languages(): JsonResponse
    {
        $data = [];

        foreach ($this->getParameter('app.locales') as $locale) {
            $data[] = [
                'code' => $locale,
                'value' => $this->get('translator')->trans($locale, [], null, $locale),
            ];
        }

        return $this->json($data);
    }

    /**
     * @Rest\Get("/translations/{language}")
     *
     * @param string $language
     *
     * @return JsonResponse
     */
    public function translations(string $language): JsonResponse
    {
        if (!in_array($language, $this->getParameter('app.locales'))) {
            throw $this->createNotFoundException('Locale '.$language.' does not exists.');
        }

        $data = [];

        foreach ($this->get('translator')->getCatalogue($language)->all('messages') as $key => $value) {
            $data[$key] = $value;
        }

        return $this->json($data);
    }
}
