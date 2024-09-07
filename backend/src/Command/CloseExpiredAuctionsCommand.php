<?php

namespace App\Command;

use App\Message\CloseExpiredAuctionMessage;
use App\Repository\ItemRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:close-expired-auctions',
    description: 'Close all expired auctions',
)]
class CloseExpiredAuctionsCommand extends Command
{
    public function __construct(
        private ItemRepository $itemRepository,
        private MessageBusInterface $messageBus
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $expiredItems = $this->itemRepository->findExpiredAuctions();

        foreach ($expiredItems as $item) {
            $this->messageBus->dispatch(new CloseExpiredAuctionMessage($item->getId()));
            $output->writeln(sprintf('Dispatched close auction message for item: %s', $item->getName()));
        }

        $output->writeln('All close auction messages have been dispatched.');

        return Command::SUCCESS;
    }
}
