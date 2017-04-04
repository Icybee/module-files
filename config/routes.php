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
use ICanBoogie\Routing\RouteDefinition as Route;

return [

	'files:show' => [

		Route::PATTERN => '/files/<uuid:{:uuid:}><extension:[\.a-z]*>',
		Route::CONTROLLER => FilesController::class,
		Route::ACTION => Make::ACTION_SHOW,
		Route::VIA => Request::METHOD_GET,

	],

	'files:download' => [

		Route::PATTERN => '/files/download/<uuid:{:uuid:}><extension:[\.a-z]*>',
		Route::CONTROLLER => FilesController::class,
		Route::ACTION => 'download',
		Route::VIA => Request::METHOD_GET,

	],

	'files:protected:show' => [

		Route::PATTERN => '/files/<nid:\d+><extension:[\.a-z]*>',
		Route::CONTROLLER => FilesAdminController::class,
		Route::ACTION => Make::ACTION_SHOW,

	],

	'files:protected:download' => [

		Route::PATTERN => '/files/download/<nid:\d+><extension:[\.a-z]*>',
		Route::CONTROLLER => FilesAdminController::class,
		Route::ACTION => 'download',

	]

] + Make::admin('files', FilesAdminController::class, [

	Make::OPTION_ID_NAME => 'nid'

]);
