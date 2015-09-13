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
use ICanBoogie\Routing\Controller;
use ICanBoogie\Binding\Routing\ControllerBindings;
use ICanBoogie\Binding\Routing\ForwardUndefinedPropertiesToApplication;

/**
 * Files public controller.
 */
class FilesController extends Controller
{
	use Controller\ActionTrait;
	use ControllerBindings;
	use ForwardUndefinedPropertiesToApplication;

	/**
	 * @param string $uuid
	 *
	 * @return \ICanBoogie\HTTP\Response
	 */
	public function action_get_show($uuid)
	{
		$route = $this
			->routes['api:files:show']
			->format([ 'uuid' => $uuid ]);

		return Request::from([

			'uri' => $route,
			'headers' => $this->request->headers

		])->send();
	}

	/**
	 * @param string $uuid
	 *
	 * @return \ICanBoogie\HTTP\Response
	 */
	public function action_get_download($uuid)
	{
		$route = $this
			->routes['api:files:download']
			->format([ 'uuid' => $uuid ]);

		return Request::from([

			'uri' => $route,
			'headers' => $this->request->headers

		])->send();
	}
}
