<?php

namespace App\Service;

use App\Entity\Item;
use App\Entity\Bid;
use TCPDF;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AuctionBillService
{
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function generateBill(Item $item, Bid $winningBid): string
    {
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Your Auction Site');
        $pdf->SetTitle('Auction Bill - ' . $item->getName());
        $pdf->SetSubject('Bill for Auction Item: ' . $item->getName());

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 12);

        // Add content
        $html = $this->generateBillContent($item, $winningBid);
        $pdf->writeHTML($html, true, false, true, false, '');

        // Close and output PDF document as a string
        return $pdf->Output('auction_bill.pdf', 'S');
    }

    private function generateBillContent(Item $item, Bid $winningBid): string
    {
        $content = '
        <h1>Auction Bill</h1>
        <hr>
        <h2>Item Details</h2>
        <table>
            <tr><td><strong>Item Name:</strong></td><td>' . $item->getName() . '</td></tr>
            <tr><td><strong>Item Description:</strong></td><td>' . $item->getDescription() . '</td></tr>
            <tr><td><strong>Auction End Date:</strong></td><td>' . $item->getAuctionEndTime()->format('Y-m-d H:i:s') . '</td></tr>
        </table>
        <h2>Winning Bid Details</h2>
        <table>
            <tr><td><strong>Winning Bidder:</strong></td><td>' . $winningBid->getBidder()->getName() . '</td></tr>
            <tr><td><strong>Winning Bid Amount:</strong></td><td>$' . number_format($winningBid->getAmount(), 2) . '</td></tr>
            <tr><td><strong>Bid Time:</strong></td><td>' . $winningBid->getBidTime()->format('Y-m-d H:i:s') . '</td></tr>
        </table>
        <hr>
        <p>Congratulations on winning this auction! Please process the payment for your winning bid amount.</p>
        <p>Thank you for participating in our auction.</p>
        ';

        return $content;
    }

    public function generateBillUrl(Bid $bid): string
    {
        return $this->urlGenerator->generate('download_bill', [
            'bidUuid' => $bid->getUuid()
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
