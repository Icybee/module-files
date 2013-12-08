<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Files;

/**
 * A block to manage files.
 */
class ManageBlock extends \Icybee\Modules\Nodes\ManageBlock
{
	static protected function add_assets(\Brickrouge\Document $document)
	{
		parent::add_assets($document);

		$document->css->add(DIR . '/public/manage.css');
	}

	public function __construct(Module $module, array $attributes)
	{
		parent::__construct
		(
			$module, $attributes + array
			(
				self::T_COLUMNS_ORDER => array('title', 'size', 'download', 'is_online', 'uid', 'mime', 'updated_at')
			)
		);
	}

	/**
	 * Adds the following columns:
	 *
	 * - `mime`: An instance of {@link ManageBlock\MimeColumn}.
	 * - `size`: An instance of {@link ManageBlock\SizeColumn}.
	 * - `download`: An instance of {@link ManageBlock\DownloadColumn}.
	 *
	 * @return array
	 */
	protected function get_available_columns()
	{
		return array_merge(parent::get_available_columns(), array
		(
			File::MIME => __CLASS__ . '\MimeColumn',
			File::SIZE => __CLASS__ . '\SizeColumn',
			'download' => __CLASS__ . '\DownloadColumn'
		));
	}
}

namespace Icybee\Modules\Files\ManageBlock;

use ICanBoogie\ActiveRecord\Query;

use Brickrouge\A;

use Icybee\ManageBlock\Column;
use Icybee\ManageBlock\FilterDecorator;

/**
 * Representation of the `mime` column.
 */
class MimeColumn extends Column
{
	public function render_cell($record)
	{
		return new FilterDecorator($record, $this->id, $this->manager->is_filtering($this->id));
	}
}

/**
 * Representation of the `size` column.
 */
class SizeColumn extends \Icybee\ManageBlock\SizeColumn
{
	public function __construct(\Icybee\ManageBlock $manager, $id, array $options=array())
	{
		parent::__construct
		(
			$manager, $id, $options + array
			(
				'class' => 'cell-fitted pull-right',
				'filters' => array
				(
					'options' => array
					(
						'=l' => 'Large',
						'=m' => 'Medium',
						'=s' => 'Small'
					)
				)
			)
		);
	}

	/**
	 * Adds support for the `size` filter.
	 */
	public function alter_filters(array $filters, array $modifiers)
	{
		$filters = parent::alter_filters($filters, $modifiers);

		if (isset($modifiers['size']))
		{
			$value = $modifiers['size'];

			if (in_array($value, array('l', 'm', 's')))
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
	 */
	public function alter_query_with_filter(Query $query, $filter_value)
	{
		if ($filter_value)
		{
			list($avg, $max, $min) = $query->model->similar_site->select('AVG(size), MAX(size), MIN(size)')->one(\PDO::FETCH_NUM);

			$bounds = array
			(
				$min,
				round($avg - ($avg - $min) / 3),
				round($avg),
				round($avg + ($max - $avg) / 3),
				$max
			);

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

/**
 * Representation of the `download` column.
 */
class DownloadColumn extends \Icybee\ManageBlock\Column
{
	public function __construct(\Icybee\ManageBlock $manager, $id, array $options=array())
	{
		parent::__construct
		(
			$manager, $id, array
			(
				'orderable' => false,
				'title' => null
			)
		);
	}

	public function render_cell($record)
	{
		return new A
		(
			'', $record->url('download'), array
			(
				'class' => 'download',
				'title' => $this->manager->t('Download file')
			)
		);
	}
}