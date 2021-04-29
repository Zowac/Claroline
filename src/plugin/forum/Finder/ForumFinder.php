<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\ForumBundle\Finder;

use Claroline\AppBundle\API\Finder\AbstractFinder;
use Doctrine\ORM\QueryBuilder;

class ForumFinder extends AbstractFinder
{
    public static function getClass(): string
    {
        return 'Claroline\ForumBundle\Entity\Forum';
    }

    public function configureQueryBuilder(QueryBuilder $qb, array $searches = [], array $sortBy = null, array $options = ['count' => false, 'page' => 0, 'limit' => -1])
    {
        foreach ($searches as $filterName => $filterValue) {
            switch ($filterName) {
              default:
                $this->setDefaults($qb, $filterName, $filterValue);
            }
        }

        return $qb;
    }

    public function getFilters()
    {
        return [
          'validationMode' => [
            'type' => 'integer',
            'description' => 'The forum validation mode',
          ],
          'maxComment' => [
            'type' => 'integer',
            'description' => 'The max amount of sub comments per messages',
          ],
        ];
    }
}
