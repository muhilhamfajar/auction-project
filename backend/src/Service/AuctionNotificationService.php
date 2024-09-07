<?php

namespace App\Service;

use App\Entity\Item;
use App\Entity\Bid;
use App\Entity\User;
use App\Entity\BidConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mime\Part\DataPart;

class AuctionNotificationService
{
    private string $webUrl;
    private string $mailerFromAddress;

    public function __construct(
        private MailerInterface $mailer,
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
        private AuctionBillService $auctionBillService
    ) {
        $this->webUrl = $this->params->get('app.web_url');
        $this->mailerFromAddress = $this->params->get('mailer_from_address');
    }

    public function sendNewBidNotification(Item $item, Bid $newBid, Bid $userBid): void
    {
        $email = (new TemplatedEmail())
            ->from($this->mailerFromAddress)
            ->to($userBid->getBidder()->getUsername())
            ->subject('New bid on ' . $item->getName())
            ->htmlTemplate('emails/new_bid_notification.html.twig')
            ->context([
                'item' => $item,
                'newBid' => $newBid,
                'user' => $userBid->getBidder(),
                'itemUrl' => $this->generateItemUrl($item),
                'userBid' => $userBid
            ]);

        $this->mailer->send($email);
    }

    public function sendAuctionEndedNotification(Item $item, User $notifiedUser, ?Bid $userHighestBid): void
    {
        $email = (new TemplatedEmail())
            ->from($this->mailerFromAddress)
            ->to($notifiedUser->getUsername())
            ->subject('Auction ended for ' . $item->getName())
            ->htmlTemplate('emails/auction_ended_notification.html.twig')
            ->context([
                'item' => $item,
                'user' => $notifiedUser,
                'userHighestBid' => $userHighestBid,
                'itemUrl' => $this->generateItemUrl($item),
            ]);

        $this->mailer->send($email);
    }

    public function sendAutoBidLimitExceededNotification(Item $item, BidConfig $bidConfig): void
    {
        $email = (new TemplatedEmail())
            ->from($this->mailerFromAddress)
            ->to($bidConfig->getUser()->getUsername())
            ->subject('Auto-bid limit reached for ' . $item->getName())
            ->htmlTemplate('emails/auto_bid_limit_exceeded_notification.html.twig')
            ->context([
                'item' => $item,
                'user' => $bidConfig->getUser(),
                'bidConfig' => $bidConfig,
                'itemUrl' => $this->generateItemUrl($item),
                'webUrl' => $this->webUrl,
            ]);

        $this->mailer->send($email);
    }

    public function sendWinnerNotification(Item $item, Bid $winningBid): void
    {
        $pdfBill = $this->auctionBillService->generateBill($item, $winningBid);

        $email = (new TemplatedEmail())
            ->from($this->mailerFromAddress)
            ->to($winningBid->getBidder()->getUsername())
            ->subject('Congratulations! You won the auction for ' . $item->getName())
            ->htmlTemplate('emails/auction_winner.html.twig')
            ->context([
                'item' => $item,
                'bid' => $winningBid,
                'user' => $winningBid->getBidder(),
                'itemUrl' => $this->generateItemUrl($item),
            ])
            ->addPart(new DataPart($pdfBill, 'auction_bill.pdf', 'application/pdf'));

        $this->mailer->send($email);
    }

    public function sendLoserNotification(Item $item, Bid $winningBid, array $loserData): void
    {
        $email = (new TemplatedEmail())
            ->from($this->mailerFromAddress)
            ->to($loserData['username'])
            ->subject('Auction ended for ' . $item->getName())
            ->htmlTemplate('emails/auction_loser.html.twig')
            ->context([
                'item' => $item,
                'winningBid' => $winningBid,
                'loserUsername' => $loserData['name'],
                'loserHighestBid' => $loserData['highestBidAmount'],
                'itemUrl' => $this->generateItemUrl($item),
            ]);

        $this->mailer->send($email);
    }

    private function generateItemUrl(Item $item): string
    {
        return $this->webUrl . '/items/' . $item->getUuid();
    }
}
