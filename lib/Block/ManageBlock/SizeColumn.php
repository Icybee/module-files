<?php

namespace Icybee\Modules\Files\Block\ManageBlock;

use ICanBoogie\ActiveRecord\Query;

use Icybee\Block\ManageBlock;

/**
 * Representation of the `size` column.
 */
class SizeColumn extends ManageBlock\SizeColumn
{
	public function __construct(ManageBlock $manager, $id, array $options = [])
	{
		parent::__construct($manager, $id, $options + [

			'class' => 'cell-fitted pull-right',
			'filters' => [

				'options' => [

					'=l' => 'Large',
					'=m' => 'Medium',
					'=s' => 'Small'

				]
			]
		]);
	}

	/**
	 * Adds support for the `size` filter.
	 *
	 * @inheritdoc
	 */
	public function alter_filters(array $filters, array $modifiers)
	{
		$filters = parent::alter_filters($filters, $modifiers);

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

		return $filters;
	}

	/**
	 * Adds support for the `size` filter.
	 *
	 * @inheritdoc
	 */
	public function alter_query_with_filter(Query $query, $filter_value)
	{
		if ($filter_value)
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

			switch ($filter_value)
			{
				case 'l': $query->and('size >= ?', $bounds[3]); break;
				case 'm': $query->and('size >= ? AND size < ?', $bounds[2], $bounds[3]); break;
				case 's': $query->and('size < ?', $bounds[2]); break;
			}
		}

		return $query;
	}
}
