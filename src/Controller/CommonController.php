<?php

declare(strict_types=1);

namespace Controller;

use Entity\Household;
use Pagination\Paginator;
use Punic\Language;
use Repository\AssistanceRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\Country\Countries;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Intl\Currencies;
use Symfony\Contracts\Translation\TranslatorInterface;
use UnexpectedValueException;
use Utils\BeneficiaryService;
use Utils\ProjectService;
use ZipArchive;

class CommonController extends AbstractController
{
    public function __construct(private readonly Countries $countries, private readonly string $translationsDir, private readonly TranslatorInterface $translator, private readonly BeneficiaryService $beneficiaryService, private readonly ProjectService $projectService)
    {
    }

    /**
     * @Rest\Get("/web-app/v1/summaries")
     *
     *
     */
    public function summaries(Request $request, AssistanceRepository $assistanceRepository): JsonResponse
    {
        $countryIso3 = $request->headers->get('country');
        if (is_null($countryIso3)) {
            throw new BadRequestHttpException('Missing country header');
        }

        $result = [];
        foreach ($request->query->get('code', []) as $code) {
            $result[] = match ($code) {
                'total_registrations' => [
                    'code' => $code,
                    'value' => $this->beneficiaryService->countAll($countryIso3),
                ],
                'active_projects' => [
                    'code' => $code,
                    'value' => $this->projectService->countActive($countryIso3),
                ],
                'enrolled_beneficiaries' => [
                    'code' => $code,
                    'value' => $this->getDoctrine()->getRepository(Household::class)->countUnarchivedByCountry(
                        $countryIso3
                    ),
                ],
                'served_beneficiaries' => [
                    'code' => $code,
                    'value' => $this->beneficiaryService->countAllServed($countryIso3),
                ],
                'completed_assistances' => ['code' => $code, 'value' => $assistanceRepository->countCompleted($countryIso3)],
                default => throw new BadRequestHttpException('Invalid query parameter code.' . $code),
            };
        }

        return $this->json(new Paginator($result));
    }

    /**
     * @Rest\Get("/web-app/v1/icons")
     * @Cache(expires="+12 hours", public=true)
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
     * @Cache(expires="+12 hours", public=true)
     */
    public function languages(): JsonResponse
    {
        $data = [];

        foreach ($this->getParameter('app.locales') as $locale) {
            $data[] = [
                'code' => $locale,
                'value' => Language::getName($locale),
            ];
        }

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/currencies")
     * @Cache(expires="+12 hours", public=true)
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
     * @Cache(expires="+12 hours", public=true)
     *
     *
     */
    public function translations(string $language): JsonResponse
    {
        if (!in_array($language, $this->getParameter('app.locales'))) {
            throw $this->createNotFoundException('Locale ' . $language . ' does not exists.');
        }

        $data = [];

        $domains = $this->translator->getCatalogue($language)->getDomains();

        foreach ($domains as $domain) {
            foreach ($this->translator->getCatalogue($language)->all($domain) as $key => $value) {
                $data[$key] = $value;
            }
        }

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/translations-xml")
     * @Cache(expires="+12 hours", public=true)
     *
     */
    public function getTranslationsXml(): BinaryFileResponse
    {
        $finder = new Finder();

        $finder->files()
            ->in($this->translationsDir)
            ->name('*.xlf') //only files with translations
            ->notName('*.en.xlf'); //exclude source keys
        if (!$finder->hasResults()) {
            throw new UnexpectedValueException('No translations found');
        }

        $filename = 'translations' . time() . '.zip';
        $zip = new ZipArchive();
        $zip->open($filename, ZipArchive::CREATE);
        foreach ($finder as $file) {
            $zip->addFile($file->getRealPath(), $file->getFilename()); //add file but flatten path
        }
        $zip->close();

        $response = new BinaryFileResponse($filename);

        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Length', filesize($filename));
        $response->deleteFileAfterSend();

        return $response;
    }

    /**
     * @Rest\Get("/web-app/v1/adms")
     *
     *
     */
    public function adms(Request $request): JsonResponse
    {
        $countryIso3 = $request->headers->get('country');
        if (is_null($countryIso3)) {
            throw new BadRequestHttpException('Missing country header');
        }

        $country = $this->countries->getCountry($countryIso3);
        if (null === $country) {
            throw $this->createNotFoundException('Country ' . $countryIso3 . ' does not exists.');
        }

        return $this->json([
            'adm1' => $this->translator->trans($country->getAdm1Name()),
            'adm2' => $this->translator->trans($country->getAdm2Name()),
            'adm3' => $this->translator->trans($country->getAdm3Name()),
            'adm4' => $this->translator->trans($country->getAdm4Name()),
        ]);
    }
}
