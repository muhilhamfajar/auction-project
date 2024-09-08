<?php

namespace App\Controller;

use App\Entity\Bid;
use App\Service\AuctionBillService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Security;

#[OA\Tag(name: 'Bills')]
class BillController extends AbstractController
{
    #[Route('/download-bill/{bidUuid}', name: 'download_bill', methods: ['GET'])]
    #[OA\Get(
        path: '/download-bill/{bidUuid}',
        summary: 'Download bill for a winning bid',
        description: 'Generates and downloads a PDF bill for a winning bid'
    )]
    #[OA\Parameter(
        name: 'bidUuid',
        in: 'path',
        description: 'UUID of the winning bid',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful operation',
        content: new OA\MediaType(
            mediaType: 'application/pdf',
            schema: new OA\Schema(type: 'string', format: 'binary')
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Bill not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function downloadBill(
        string $bidUuid,
        EntityManagerInterface $entityManager,
        AuctionBillService $billService
    ): Response {
        $bid = $entityManager->getRepository(Bid::class)->findOneBy(['uuid' => $bidUuid]);

        if (! $bid || $bid->getStatus() !== Bid::STATUS_WON) {
            throw $this->createNotFoundException('Bill not found');
        }

        $pdfContent = $billService->generateBill($bid->getItem(), $bid);

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="auction_bill.pdf"');

        return $response;
    }
}
