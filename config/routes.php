<?php

namespace Icybee\Modules\Files;

return array
(
	'api:files:get' => array
	(
		'pattern' => '/api/<_operation_module:files|images>/<hexnid:[0-9a-z]{8}>-<hash:[0-9a-z]{48}>',
		'controller' => __NAMESPACE__ . '\GetOperation',
		'via' => 'GET'
	),

	'api:files:download' => array
	(
		'pattern' => '/api/:_operation_module/<hexnid:[0-9a-z]{8}>-<hash:[0-9a-z]{48}>/download',
		'controller' => __NAMESPACE__ . '\DownloadOperation',
		'via' => 'GET'
	)
);