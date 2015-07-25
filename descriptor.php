<?php

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

				'path' => 'varchar',
				'mime' => 'varchar',
				'size' => [ 'integer', 'unsigned' => true ],
				'description' => 'text'

			]
		]
	],

	Descriptor::NS => __NAMESPACE__,
	Descriptor::REQUIRED => true,
	Descriptor::TITLE => "Files"

];
