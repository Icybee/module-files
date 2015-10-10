<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Files\Block\ManageBlock;

use Icybee\Block\ManageBlock;

/**
 * Representation of the `size` column.
 */
class SizeColumn extends ManageBlock\SizeColumn
{
	use ManageBlock\CriterionColumnTrait;

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
}
