<?php

namespace OroB2B\Bundle\ProductBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductUnitRepository extends EntityRepository
{
    /**
     * @param Product $product
     * @return ProductUnit[]
     */
    public function getProductUnits(Product $product)
    {
        return $this->getProductUnitsQueryBuilder($product)->getQuery()->getResult();
    }

    /**
     * @param Product $product
     *
     * @return QueryBuilder
     */
    public function getProductUnitsQueryBuilder(Product $product)
    {
        $qb = $this->createQueryBuilder('unit');
        $qb
            ->select('unit')
            ->join(
                'OroB2BProductBundle:ProductUnitPrecision',
                'productUnitPrecision',
                Join::WITH,
                $qb->expr()->eq('productUnitPrecision.unit', 'unit')
            )
            ->addOrderBy('unit.code')
            ->where($qb->expr()->eq('productUnitPrecision.product', ':product'))
            ->setParameter('product', $product);

        return $qb;
    }
}
