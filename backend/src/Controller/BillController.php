<?php

namespace App\Controller;

use App\Entity\Bid;
use App\Service\AuctionBillService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class BillController extends AbstractController
{
    #[Route('/download-bill/{bidUuid}', name: 'download_bill')]
    public function downloadBill(
        string $bidUuid, 
        EntityManagerInterface $entityManager, 
        AuctionBillService $billService
    ): Response {
        $bid = $entityManager->getRepository(Bid::class)->findOneBy(['uuid' => $bidUuid]);

        if (!$bid || $bid->getStatus() !== Bid::STATUS_WON) {
            throw $this->createNotFoundException('Bill not found');
        }

        $pdfContent = $billService->generateBill($bid->getItem(), $bid);

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="auction_bill.pdf"');

        return $response;
    }
}