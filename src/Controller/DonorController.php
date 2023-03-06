<?php

declare(strict_types=1);

namespace Controller;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\File\UploadService;
use InputType\DonorCreateInputType;
use InputType\DonorFilterInputType;
use InputType\DonorOrderInputType;
use InputType\DonorUpdateInputType;
use InputType\Export\FormatInputType;
use Repository\DonorRepository;
use Request\Pagination;
use Entity\Donor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Utils\DonorService;
use Utils\DonorTransformData;
use Utils\ExportTableServiceInterface;

class DonorController extends AbstractController
{
    public function __construct(
        private readonly UploadService $uploadService,
        private readonly DonorService $donorService,
        private readonly ManagerRegistry $managerRegistry,
        private readonly DonorTransformData $donorTransformData,
        private readonly ExportTableServiceInterface $exportTableService,
        private readonly DonorRepository $donorRepository
    ) {
    }

    #[Rest\Get('/web-app/v1/donors/exports')]
    public function exports(FormatInputType $formatInputType): Response
    {
        $donors = $this->donorRepository->findAll();
        $exportableTable = $this->donorTransformData->transformData($donors);

        return $this->exportTableService->export($exportableTable, 'donors', $formatInputType->getType());
    }

    #[Rest\Get('/web-app/v1/donors/{id}')]
    #[Cache(public: true, lastModified: 'donor.getLastModifiedAt()')]
    public function item(Donor $donor): JsonResponse
    {
        return $this->json($donor);
    }

    #[Rest\Get('/web-app/v1/donors')]
    public function list(
        Pagination $pagination,
        DonorOrderInputType $orderBy,
        DonorFilterInputType $filter
    ): JsonResponse {
        $countrySpecifics = $this->managerRegistry->getRepository(Donor::class)
            ->findByParams($orderBy, $pagination, $filter);

        return $this->json($countrySpecifics);
    }

    #[Rest\Post('/web-app/v1/donors')]
    public function create(DonorCreateInputType $inputType): JsonResponse
    {
        $donor = $this->donorService->create($inputType);

        return $this->json($donor);
    }

    #[Rest\Put('/web-app/v1/donors/{id}')]
    public function update(Donor $donor, DonorUpdateInputType $inputType): JsonResponse
    {
        $this->donorService->update($donor, $inputType);

        return $this->json($donor);
    }

    #[Rest\Delete('/web-app/v1/donors/{id}')]
    public function delete(Donor $object): JsonResponse
    {
        $this->donorService->delete($object);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Rest\Post('/web-app/v1/donors/{id}/images')]
    public function uploadImage(Donor $donor, Request $request): JsonResponse
    {
        if (!($file = $request->files->get('file'))) {
            throw new BadRequestHttpException('File missing.');
        }

        if (!in_array($file->getMimeType(), ['image/gif', 'image/jpeg', 'image/png'])) {
            throw new BadRequestHttpException('Invalid file type.');
        }

        $url = $this->uploadService->upload($file, 'donors');

        $donor->setLogo($url);

        $this->managerRegistry->getManager()->persist($donor);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['url' => $url]);
    }
}
