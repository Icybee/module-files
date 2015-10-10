<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Files\Facets;

use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\Facets\Criterion;

class SizeCriterion extends Criterion
{
	/**
	 * Adds support for the `size` filter.
	 *
	 * @inheritdoc
	 */
	public function alter_conditions(array &$filters, array $modifiers)
	{
		parent::alter_conditions($filters, $modifiers);

		if (isset($modifiers['size']))
		{
			$value = $modifiers['size'];

			if (in_array($value, [ 'l', 'm', 's' ]))
			{
				$filters['size'] = $value;
			}
			else
			{
				unset($filters['size']);
			}
		}
	}

	/**
	 * Adds support for the `size` filter.
	 *
	 * @inheritdoc
	 */
	public function alter_query_with_value(Query $query, $value)
	{
		if ($value)
		{
			list($avg, $max, $min) = $query->model
				->similar_site
				->select('AVG(size), MAX(size), MIN(size)')
				->one(\PDO::FETCH_NUM);

			$bounds = [

				$min,
				round($avg - ($avg - $min) / 3),
				round($avg),
				round($avg + ($max - $avg) / 3),
				$max

			];

			switch ($value)
			{
				case 'l': $query->and('size >= ?', $bounds[3]); break;
				case 'm': $query->and('size >= ? AND size < ?', $bounds[2], $bounds[3]); break;
				case 's': $query->and('size < ?', $bounds[2]); break;
			}
		}

		return $query;
	}
}
