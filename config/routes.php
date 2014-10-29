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

use ICanBoogie\HTTP\Request;

return [

	'api:files/get' => [

		'pattern' => '/api/files/<uuid:[0-9a-z\-]{36}>',
		'controller' => __NAMESPACE__ . '\GetOperation',
		'via' => Request::METHOD_GET

	],

	'api:files/download' => [

		'pattern' => '/api/files/<uuid:[0-9a-z\-]{36}>/download',
		'controller' => __NAMESPACE__ . '\DownloadOperation',
		'via' => Request::METHOD_GET

	],

	'files/get' => [

		'pattern' => '/files/<uuid:[0-9a-z\-]{36}>',
		'controller' => __NAMESPACE__ . '\GetController',
		'via' => Request::METHOD_GET

	],

	'files/download' => [

		'pattern' => '/files/<uuid:[0-9a-z\-]{36}>/download',
		'controller' => __NAMESPACE__ . '\DownloadController',
		'via' => Request::METHOD_GET

	]
];
