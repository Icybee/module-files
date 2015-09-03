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

use ICanBoogie;

$hooks = Hooks::class . '::';

return [

	ICanBoogie\Core::class . '::lazy_get_file_storage_index' => $hooks . 'get_file_storage_index',
	ICanBoogie\Core::class . '::lazy_get_file_storage' => $hooks . 'get_file_storage'

];
