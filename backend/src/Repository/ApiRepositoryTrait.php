<?php

namespace App\Repository;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

trait ApiRepositoryTrait
{
    abstract public function getAlias(): string;

    public function createQueryBuilder(string $alias, string $indexBy = null): QueryBuilder
    {
        return parent::createQueryBuilder($alias, $indexBy);
    }

    public function search(Request $request)
    {
        $qb = $this->createQueryBuilder($this->getAlias());
        $this->applyFilters($qb, $request);
        $this->applyOrder($qb, $request);
        $this->applyPagination($qb, $request);
        $this->applySoftDelete($qb);
        $this->applyCustomQuery($qb);

        return $qb->getQuery()->getResult();
    }

    public function countByRequest(Request $request): int
    {
        $qb = $this->createQueryBuilder($this->getAlias());
        $this->applyFilters($qb, $request);
        $this->applySoftDelete($qb);
        $this->applyCustomQuery($qb);

        $qb->select(sprintf('COUNT(DISTINCT %s.id)', $this->getAlias()));

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function count(array $criteria = []): int
    {
        $qb = $this->createQueryBuilder($this->getAlias());
        $qb->select(sprintf('COUNT(DISTINCT %s.id)', $this->getAlias()));

        foreach ($criteria as $field => $value) {
            $qb->andWhere(sprintf('%s.%s = :%s', $this->getAlias(), $field, $field))
               ->setParameter($field, $value);
        }

        $this->applySoftDelete($qb);
        $this->applyCustomQuery($qb);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }


    protected function applyFilters(QueryBuilder $qb, Request $request): void
    {
        $filters = $request->query->all();
        unset($filters['page'], $filters['limit'], $filters['orderBy']);

        foreach ($filters as $field => $value) {
            if ($value === null) {
                continue;
            }

            if ($value === 'null') {
                $qb->andWhere(sprintf('%s.%s IS NULL', $this->getAlias(), $field));
            } elseif (is_array($value)) {
                $qb->andWhere(sprintf('%s.%s IN (:%s)', $this->getAlias(), $field, $field))
                   ->setParameter($field, $value);
            } else {
                $qb->andWhere(sprintf('%s.%s = :%s', $this->getAlias(), $field, $field))
                   ->setParameter($field, $value);
            }
        }
    }

    protected function applyOrder(QueryBuilder $qb, Request $request): void
    {
        $orderBy = $request->query->all('orderBy');
        if (! is_array($orderBy) || empty($orderBy)) {
            $orderBy = ['createdAt' => 'DESC'];
        }

        foreach ($orderBy as $field => $direction) {
            if (! is_string($field) || ! is_string($direction)) {
                continue;
            }
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $qb->addOrderBy(sprintf('%s.%s', $this->getAlias(), $field), $direction);
        }
    }

    protected function applyPagination(QueryBuilder $qb, Request $request): void
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $qb->setMaxResults($limit)
           ->setFirstResult(($page - 1) * $limit);
    }

    protected function applySoftDelete(QueryBuilder $qb): void
    {
        if (method_exists($this, 'addSoftDeleteFilter')) {
            $this->addSoftDeleteFilter($qb);
        }
    }

    protected function applyCustomQuery(QueryBuilder $qb): void
    {
        if (method_exists($this, 'addCustomQuery')) {
            $this->addCustomQuery($qb);
        }
    }
}
