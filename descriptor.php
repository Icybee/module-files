<?php

namespace Icybee\Modules\Files;

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\Module\Descriptor;

return array
(
	Descriptor::CATEGORY => 'resources',
	Descriptor::DESCRIPTION => 'Foundation for file management',
	Descriptor::INHERITS => 'nodes',
	Descriptor::ID => 'files',
	Descriptor::MODELS => array
	(
		'primary' => array
		(
			Model::T_EXTENDS => 'nodes',
			Model::T_SCHEMA => array
			(
				'fields' => array
				(
					'path' => 'varchar',
					'mime' => 'varchar',
					'size' => array('integer', 'unsigned' => true),
					'description' => 'text'
				)
			)
		)
	),

	Descriptor::NS => __NAMESPACE__,
	Descriptor::REQUIRED => true,
	Descriptor::TITLE => 'Files',
	Descriptor::VERSION => '1.0'
);