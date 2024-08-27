<?php

namespace App\Service;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;

class PaginationService
{
    public function paginate(EntityRepository $repository, Request $request, array $criteria = [], array $searchFields = []): array
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $queryBuilder = $repository->createQueryBuilder('e');
        $this->applyFilters($queryBuilder, $request, $criteria);
        $this->applySearch($queryBuilder, $request, $searchFields);
        $this->applySorting($queryBuilder, $request);

        return $this->doPaginate($queryBuilder, $page, $limit);
    }

    private function applyFilters(QueryBuilder $queryBuilder, Request $request, array $criteria): void
    {
        $filters = array_merge($criteria, $request->query->all());

        unset($filters['page'], $filters['limit'], $filters['sort'], $filters['order'], $filters['q']);

        foreach ($filters as $field => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                $queryBuilder->andWhere($queryBuilder->expr()->in("e.$field", ":$field"))
                    ->setParameter($field, $value);
            } else {
                $queryBuilder->andWhere("e.$field = :$field")
                    ->setParameter($field, $value);
            }
        }
    }

    private function applySearch(QueryBuilder $queryBuilder, Request $request, array $searchFields): void
    {
        $searchTerm = $request->query->get('q');
        if ($searchTerm && ! empty($searchFields)) {
            $orX = $queryBuilder->expr()->orX();
            foreach ($searchFields as $field) {
                $orX->add($queryBuilder->expr()->like("e.$field", ":searchTerm"));
            }
            $queryBuilder->andWhere($orX)
                ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }
    }

    private function applySorting(QueryBuilder $queryBuilder, Request $request): void
    {
        $sort = $request->query->get('sort', 'id');
        $order = $request->query->get('order', 'DESC');

        if (! in_array(strtoupper($order), ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        $queryBuilder->orderBy("e.$sort", $order);
    }

    private function doPaginate(QueryBuilder $queryBuilder, int $page, int $limit): array
    {
        $paginator = new Paginator($queryBuilder);

        $totalItems = count($paginator);
        $pagesCount = (int) ceil($totalItems / $limit);

        $paginator
            ->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        return [
            'totalItems' => $totalItems,
            'itemsPerPage' => $limit,
            'currentPage' => $page,
            'totalPage' => $pagesCount,
            'data' => iterator_to_array($paginator),
        ];
    }
}
