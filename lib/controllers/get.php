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

use ICanBoogie\Binding\Routing\ForwardUndefinedPropertiesToApplication;
use ICanBoogie\HTTP\Request;
use ICanBoogie\Routing\Controller;

class GetController extends Controller
{
	use ForwardUndefinedPropertiesToApplication;

	public function action(Request $request)
	{
		$uuid = $request['uuid'];
		$route = $this->routes['api:files/get']->format([ 'uuid' => $uuid ]);
		$request = Request::from($route);

		return $request();
	}
}
