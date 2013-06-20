<?php

namespace Icybee\Modules\Files;

$hooks = __NAMESPACE__ . '\Hooks::';

return array
(
	'events' => array
	(
		'Icybee\Modules\Files\DeleteOperation::process' => $hooks . 'on_delete',
		'Icybee\Modules\Files\SaveOperation::process' => $hooks . 'on_save',
		'Icybee\Modules\Files\SaveOperation::process:before' => $hooks . 'before_save'
	)
);