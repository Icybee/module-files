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

use ICanBoogie\Binding\Routing\ControllerBindings;
use ICanBoogie\Binding\Routing\ForwardUndefinedPropertiesToApplication;
use ICanBoogie\HTTP\Request;
use ICanBoogie\Routing\Controller;

/**
 * Download controller.
 */
class DownloadController extends Controller
{
	use ControllerBindings;
	use ForwardUndefinedPropertiesToApplication;

	protected function action(Request $request)
	{
		$route = $this->routes['api:files/download']->format([ 'uuid' => $request['uuid'] ]);

		return Request::from($route)->send();
	}
}
