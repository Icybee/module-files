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
use ICanBoogie\Routing\Controller;
use ICanBoogie\Routing\Routes;

/**
 * Download controller.
 *
 * @property Routes $routes
 */
class DownloadController extends Controller
{
	protected function respond(Request $request)
	{
		$route = $this->routes['api:files/download']->format([ 'uuid' => $request['uuid'] ]);

		return Request::from($route)->send();
	}
}
