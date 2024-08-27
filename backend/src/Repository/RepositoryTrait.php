<?php

namespace App\Repository;

use Doctrine\ORM\QueryBuilder;

trait RepositoryTrait
{
    public function createFilteredQueryBuilder(array $filters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e');

        foreach ($filters as $field => $value) {
            if ($value !== null) {
                $qb->andWhere("e.$field = :$field")
                   ->setParameter($field, $value);
            }
        }

        return $qb;
    }

    public function findByFilters(array $filters = [], array $orderBy = [], int $limit = null, int $offset = null): array
    {
        $qb = $this->createFilteredQueryBuilder($filters);

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy("e.$field", $direction);
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    public function findOneByFilters(array $filters = []): ?object
    {
        $result = $this->findByFilters($filters, [], 1);
        return $result ? $result[0] : null;
    }
}
