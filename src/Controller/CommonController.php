<?php

declare(strict_types=1);

namespace Controller;

use Entity\Household;
use Pagination\Paginator;
use Psr\Log\LoggerInterface;
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
    /** @var Countries */
    private $countries;

    /** @var string */
    private $translationsDir;

    /** @var TranslatorInterface */
    private $translator;

    /** @var BeneficiaryService */
    private $beneficiaryService;

    /** @var ProjectService */
    private $projectService;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        Countries $countries,
        string $translationsDir,
        TranslatorInterface $translator,
        BeneficiaryService $beneficiaryService,
        ProjectService $projectService,
        LoggerInterface $logger
    ) {
        $this->countries = $countries;
        $this->translationsDir = $translationsDir;
        $this->translator = $translator;
        $this->beneficiaryService = $beneficiaryService;
        $this->projectService = $projectService;
        $this->logger = $logger;
    }

    /**
     * @Rest\Get("/web-app/v1/summaries")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function summaries(Request $request, AssistanceRepository $assistanceRepository): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $result = [];
        foreach ($request->query->get('code', []) as $code) {
            switch ($code) {
                case 'total_registrations':
                    $result[] = [
                        'code' => $code,
                        'value' => $this->beneficiaryService->countAll($countryIso3),
                    ];
                    break;
                case 'active_projects':
                    $result[] = [
                        'code' => $code,
                        'value' => $this->projectService->countActive($countryIso3),
                    ];
                    break;
                case 'enrolled_beneficiaries':
                    $result[] = [
                        'code' => $code,
                        'value' => $this->getDoctrine()->getRepository(Household::class)->countUnarchivedByCountry(
                            $countryIso3
                        ),
                    ];
                    break;
                case 'served_beneficiaries':
                    $result[] = [
                        'code' => $code,
                        'value' => $this->beneficiaryService->countAllServed($countryIso3),
                    ];
                    break;
                case 'completed_assistances':
                    $result[] = ['code' => $code, 'value' => $assistanceRepository->countCompleted($countryIso3)];
                    break;
                default:
                    throw new BadRequestHttpException('Invalid query parameter code.' . $code);
            }
        }

        return $this->json(new Paginator($result));
    }

    /**
     * @Rest\Get("/web-app/v1/icons")
     * @Cache(expires="+12 hours", public=true)
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
     * @Cache(expires="+12 hours", public=true)
     *
     * @return JsonResponse
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
     * @Cache(expires="+12 hours", public=true)
     *
     * @param string $language
     *
     * @return JsonResponse
     */
    public function translations(string $language): JsonResponse
    {
        if (!in_array($language, $this->getParameter('app.locales'))) {
            throw $this->createNotFoundException('Locale ' . $language . ' does not exists.');
        }

        $this->logger->debug('[translations] Loading translations for ' . $language);
        $this->logger->debug('[translations] Translations dir ' . $this->translationsDir);

        $transFiles = glob($this->translationsDir . '/*');

        if ($transFiles !== false) {
            $this->logger->debug('[translations] Translations files ' . implode(', ', $transFiles));
        } else {
            $this->logger->debug('[translations] Translations files not found (glob over translations dir failed)');
        }

        $data = [];

        $domains = $this->translator->getCatalogue($language)->getDomains();

        $this->logger->debug('[translations] Domains: ' . implode(', ', $domains));

        foreach ($domains as $domain) {
            $this->logger->debug("[translations] Domain $domain contains " . count($this->translator->getCatalogue($language)->all($domain)) . ' translations.');
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
