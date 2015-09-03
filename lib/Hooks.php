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

use ICanBoogie\Core;

use Icybee\Modules\Files\Storage\FileStorage;
use Icybee\Modules\Files\Storage\FileStorageIndex;

class Hooks
{
	/**
	 * @param Core|Binding\CoreBindings $app
	 *
	 * @return FileStorageIndex
	 */
	static public function get_file_storage_index(Core $app)
	{
		static $index;

		return $index ?: $index = new FileStorageIndex(\ICanBoogie\REPOSITORY . 'files-index');
	}

	/**
	 * @param Core|Binding\CoreBindings $app
	 *
	 * @return FileStorage
	 */
	static public function get_file_storage(Core $app)
	{
		static $manager;

		return $manager ?: $manager = new FileStorage(\ICanBoogie\REPOSITORY . 'files', $app->file_storage_index);
	}
}
