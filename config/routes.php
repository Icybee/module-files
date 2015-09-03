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

use Icybee\Modules\Files\Operation\CompatDownloadOperation;
use Icybee\Modules\Files\Operation\DownloadOperation;
use Icybee\Modules\Files\Operation\ProtectedShowOperation;
use Icybee\Modules\Files\Operation\ShowOperation;
use Icybee\Routing\RouteMaker as Make;

return [

	'api:files:show' => [

		'pattern' => '/api/files/<uuid:{:uuid:}>',
		'controller' => ShowOperation::class,
		'via' => Request::METHOD_GET

	],

	'api:files:download' => [

		'pattern' => '/api/files/<uuid:{:uuid:}>/download',
		'controller' => DownloadOperation::class,
		'via' => Request::METHOD_GET

	],

	'api:files:protected-show' => [

		'pattern' => '/api/files/<nid:\d+>',
		'controller' => ProtectedShowOperation::class,
		'via' => Request::METHOD_GET

	],

	'api:files:protected-download' => [

		'pattern' => '/api/files/<nid:\d+>/download',
		'controller' => CompatDownloadOperation::class,
		'via' => Request::METHOD_GET

	],

	'files:show' => [

		'pattern' => '/files/<uuid:{:uuid:}><extension:[\.a-z]*>',
		'controller' => Routing\FilesController::class . '#show',
		'via' => Request::METHOD_GET

	],

	'files:download' => [

		'pattern' => '/files/download/<uuid:{:uuid:}><extension:[\.a-z]*>',
		'controller' => Routing\FilesController::class . '#download',
		'via' => Request::METHOD_GET

	],

	'files:protected:show' => [

		'pattern' => '/files/<nid:\d+><extension:[\.a-z]*>',
		'controller' => Routing\FilesAdminController::class . '#show'

	],

	'files:protected:download' => [

		'pattern' => '/files/download/<nid:\d+><extension:[\.a-z]*>',
		'controller' => Routing\FilesAdminController::class . '#download'

	]

] + Make::admin('files', Routing\FilesAdminController::class, [

	'id_name' => 'nid'

]);
