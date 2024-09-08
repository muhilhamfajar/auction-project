<?php

namespace App\Repository;

use App\Entity\Bid;
use App\Entity\Item;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bid>
 */
class BidRepository extends ServiceEntityRepository
{
    use ApiRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bid::class);
    }

    public function getAlias(): string
    {
        return 'b';
    }

    public function findHighestBidForItem(Item $item): ?Bid
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.item = :item')
            ->setParameter('item', $item)
            ->orderBy('b.amount', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findHighestBidsForItems(array $items): array
    {
        $qb = $this->createQueryBuilder('b');
        $qb->select('IDENTITY(b.item) as itemId, MAX(b.amount) as maxAmount')
           ->where($qb->expr()->in('b.item', ':items'))
           ->groupBy('b.item')
           ->setParameter('items', $items);

        $maxBids = $qb->getQuery()->getResult();

        $result = [];
        foreach ($maxBids as $maxBid) {
            $bid = $this->createQueryBuilder('b')
                ->andWhere('b.item = :itemId')
                ->andWhere('b.amount = :maxAmount')
                ->setParameter('itemId', $maxBid['itemId'])
                ->setParameter('maxAmount', $maxBid['maxAmount'])
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($bid) {
                $result[$bid->getItem()->getId()] = $bid;
            }
        }

        return $result;
    }

    public function findUniqueLosingBiddersWithHighestBids(Item $item, User $winningBidder): array
    {
        $qb = $this->createQueryBuilder('b');

        return $qb->select('IDENTITY(b.bidder) as userId, u.username, u.name')
            ->addSelect('MAX(b.amount) as highestBidAmount')
            ->addSelect('MAX(b.id) as bidId')
            ->join('b.bidder', 'u')
            ->where('b.item = :item')
            ->andWhere('b.bidder != :winningBidder')
            ->setParameter('item', $item)
            ->setParameter('winningBidder', $winningBidder)
            ->groupBy('b.bidder')
            ->getQuery()
            ->getResult();
    }

    public function findUniqueBiddersByItem(Item $item, User $excludedBidder): array
    {
        $qb = $this->createQueryBuilder('b');

        return $qb->select('b')
            ->where('b.item = :item')
            ->andWhere('b.bidder != :excludedBidder')
            ->andWhere(
                $qb->expr()->eq(
                    'b.amount',
                    '(SELECT MAX(b2.amount) FROM ' . $this->getEntityName() . ' b2 
                      WHERE b2.item = b.item AND b2.bidder = b.bidder)'
                )
            )
            ->orderBy('b.amount', 'DESC')
            ->setParameter('item', $item)
            ->setParameter('excludedBidder', $excludedBidder)
            ->getQuery()
            ->getResult();
    }

    public function findLosingBids(Item $item, Bid $winningBid): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.item = :item')
            ->andWhere('b != :winningBid')
            ->setParameter('item', $item)
            ->setParameter('winningBid', $winningBid)
            ->getQuery()
            ->getResult();
    }

    public function findCurrentBidsForUser(User $user, ?string $search, string $sort, string $order): array
    {
        $qb = $this->createQueryBuilder('b')
        ->select('b')
        ->join('b.item', 'i')
        ->leftJoin(Bid::class, 'b2', Join::WITH, 'b2.item = i')
        ->where('b.bidder = :user')
        ->andWhere('i.status = :statusOpen')
        ->andWhere('b.amount = (SELECT MAX(b3.amount) FROM App\Entity\Bid b3 WHERE b3.item = i AND b3.bidder = :user)')
        ->groupBy('i.id, b.id')
        ->setParameter('user', $user)
        ->setParameter('statusOpen', Item::STATUS_ACTIVE);

        $this->applySearchAndSort($qb, $search, $sort, $order);

        return $qb->getQuery()->getResult();
    }

    public function findBidHistoryForUser(User $user, ?string $search, string $sort, string $order): array
    {
        $qb = $this->createQueryBuilder('b')
            ->select('b')
            ->join('b.item', 'i')
            ->where('b.bidder = :user')
            ->setParameter('user', $user);

        $this->applySearchAndSort($qb, $search, $sort, $order);

        return $qb->getQuery()->getResult();
    }

    public function findAwardedItemsForUser(User $user, ?string $search, string $sort, string $order): array
    {
        $qb = $this->createQueryBuilder('b')
            ->select('b')
            ->join('b.item', 'i')
            ->where('b.bidder = :user')
            ->andWhere('b.status = :statusWin')
            ->andWhere('i.status = :statusClosed')
            ->setParameter('user', $user)
            ->setParameter('statusWin', Bid::STATUS_WON)
            ->setParameter('statusClosed', Item::STATUS_EXPIRED);

        $this->applySearchAndSort($qb, $search, $sort, $order);

        return $qb->getQuery()->getResult();
    }

    private function applySearchAndSort($qb, ?string $search, string $sort, string $order): void
    {
        if ($search) {
            $qb->andWhere('i.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        switch ($sort) {
            case 'item.name':
                $qb->orderBy('i.name', $order);
                break;
            case 'amount':
                $qb->orderBy('b.amount', $order);
                break;
            case 'bidTime':
                $qb->orderBy('b.bidTime', $order);
                break;
            default:
                $qb->orderBy('b.bidTime', 'DESC');
        }
    }

    public function markExistingBidsAsLost(Item $item, Bid $newBid): void
    {
        $qb = $this->createQueryBuilder('b');

        $qb->update()
            ->set('b.status', ':lostStatus')
            ->where('b.item = :item')
            ->andWhere('b.id != :newBid')
            ->setParameter('item', $item)
            ->setParameter('newBid', $newBid)
            ->setParameter('lostStatus', Bid::STATUS_LOST);

        $qb->getQuery()->execute();
    }
}
