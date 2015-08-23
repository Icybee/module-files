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
use ICanBoogie\Module\CoreBindings;
use ICanBoogie\Routing\Controller;

class CompatDownloadController extends Controller
{
	use ForwardUndefinedPropertiesToApplication;
	use CoreBindings;

	public function action(Request $request)
	{
		/* @var $record File */

		$nid = $request['nid'];
		$record = $this->modules['files'][$nid];
		$request = Request::from($record->url('download'));

		return $request();
	}
}
