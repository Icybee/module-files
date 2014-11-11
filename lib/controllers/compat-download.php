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

class CompatDownloadController extends Controller
{
	public function __invoke(Request $request)
	{
		$nid = $request['nid'];
		$record = $this->app->modules['files'][$nid];
		$request = Request::from($record->url('download'));

		return $request();
	}
}
