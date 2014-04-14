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
use ICanBoogie\Operation;

return [

	'api:files/get' => [

		'pattern' => '/api/files/<nid:\d+>',
		'controller' => __NAMESPACE__ . '\GetOperation',
		'via' => Request::METHOD_GET,
		'param_translation_list' => [

			'nid' => Operation::KEY

		]

	]

];