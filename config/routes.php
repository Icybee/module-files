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

use Icybee\Routing\RouteMaker as Make;

return [

	'api:files/get' => [

		'pattern' => '/api/files/<uuid:[0-9a-z\-]{36}>',
		'controller' => GetOperation::class,
		'via' => Request::METHOD_GET

	],

	'api:files/download' => [

		'pattern' => '/api/files/<uuid:[0-9a-z\-]{36}>/download',
		'controller' => DownloadOperation::class,
		'via' => Request::METHOD_GET

	],

	'api:files/compat-get' => [

		'pattern' => '/api/files/<nid:\d+>',
		'controller' => CompatGetOperation::class,
		'via' => Request::METHOD_GET

	],

	'api:files/compat-download' => [

		'pattern' => '/api/files/<nid:\d+>/download',
		'controller' => CompatDownloadOperation::class,
		'via' => Request::METHOD_GET

	],

	'files/get' => [

		'pattern' => '/files/<uuid:[0-9a-z\-]{36}>',
		'controller' => GetController::class,
		'via' => Request::METHOD_GET

	],

	'files/download' => [

		'pattern' => '/files/<uuid:[0-9a-z\-]{36}>/download',
		'controller' => DownloadController::class,
		'via' => Request::METHOD_GET

	]

] + Make::admin('files', Routing\FilesAdminController::class);
