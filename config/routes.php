<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Files\Routing;

use ICanBoogie\HTTP\Request;

use Icybee\Routing\RouteMaker as Make;

return [

	'files:show' => [

		'pattern' => '/files/<uuid:{:uuid:}><extension:[\.a-z]*>',
		'controller' => FilesController::class . '#show',
		'via' => Request::METHOD_GET

	],

	'files:download' => [

		'pattern' => '/files/download/<uuid:{:uuid:}><extension:[\.a-z]*>',
		'controller' => FilesController::class . '#download',
		'via' => Request::METHOD_GET

	],

	'files:protected:show' => [

		'pattern' => '/files/<nid:\d+><extension:[\.a-z]*>',
		'controller' => FilesAdminController::class . '#show'

	],

	'files:protected:download' => [

		'pattern' => '/files/download/<nid:\d+><extension:[\.a-z]*>',
		'controller' => FilesAdminController::class . '#download'

	]

] + Make::admin('files', FilesAdminController::class, [

	'id_name' => 'nid'

]);
