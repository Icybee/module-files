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

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\Module\Descriptor;

return [

	Descriptor::CATEGORY => 'resources',
	Descriptor::DESCRIPTION => 'Foundation for file management',
	Descriptor::INHERITS => 'nodes',
	Descriptor::ID => 'files',
	Descriptor::MODELS => [

		'primary' => [

			Model::EXTENDING => 'nodes',
			Model::SCHEMA => [

				'size' => [ 'integer', 'unsigned' => true ],
				'mime' => [ 'varchar', 'charset' => 'ascii/general_ci' ],
				'extension' => [ 'varchar', 16, 'charset' => 'ascii/general_ci' ],
				'description' => 'text'

			]
		]
	],

	Descriptor::NS => __NAMESPACE__,
	Descriptor::REQUIRED => true,
	Descriptor::TITLE => "Files"

];
