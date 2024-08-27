<?php

namespace App\Repository;

use App\Entity\Bid;
use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}
