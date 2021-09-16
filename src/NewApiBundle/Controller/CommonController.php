<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Household;
use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Country\Countries;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Translation\TranslatorInterface;

class CommonController extends AbstractController
{
    /** @var Countries */
    private $countries;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(Countries $countries, TranslatorInterface $translator)
    {
        $this->countries = $countries;
        $this->translator = $translator;
    }

    /**
     * @Rest\Get("/web-app/v1/summaries")
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
     * @Rest\Get("/web-app/v1/icons")
     * @Cache(expires="+5 days", public=true)
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

        foreach ($this->getParameter('icons_vulnerability_criteria') as $key => $svg) {
            $data[] = ['key' => $key, 'svg' => $svg];
        }

        foreach ($this->getParameter('icons_product_category_types') as $key => $svg) {
            $data[] = ['key' => $key, 'svg' => $svg];
        }

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/languages")
     * @Cache(expires="+5 days", public=true)
     *
     * @return JsonResponse
     */
    public function languages(): JsonResponse
    {
        $data = [];

        foreach ($this->getParameter('app.locales') as $locale) {
            $data[] = [
                'code' => $locale,
                'value' => \Punic\Language::getName($locale),
            ];
        }

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/currencies")
     * @Cache(expires="+5 days", public=true)
     *
     * @return JsonResponse
     */
    public function currencies(): JsonResponse
    {
        $data = [];

        foreach ($this->getParameter('app.currencies') as $currency) {
            $data[] = [
                'code' => $currency,
                'value' => Currencies::getName($currency),
            ];
        }

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/translations/{language}")
     * @Cache(expires="+5 days", public=true)
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

    /**
     * @Rest\Get("/web-app/v1/adms")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function adms(Request $request): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $country = $this->countries->getCountry($countryIso3);
        if (null === $country) {
            throw $this->createNotFoundException('Country '.$countryIso3.' does not exists.');
        }

        return $this->json([
            'adm1' => $this->translator->trans($country->getAdm1Name()),
            'adm2' => $this->translator->trans($country->getAdm2Name()),
            'adm3' => $this->translator->trans($country->getAdm3Name()),
            'adm4' => $this->translator->trans($country->getAdm4Name()),
        ]);
    }
}
