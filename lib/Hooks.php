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

use ICanBoogie\AppConfig;
use ICanBoogie\Application;

use Icybee\Modules\Files\Storage\FileStorage;
use Icybee\Modules\Files\Storage\FileStorageIndex;

class Hooks
{
	/**
	 * @param Application $app
	 *
	 * @return FileStorageIndex
	 */
	static public function get_file_storage_index(Application $app)
	{
		static $index;

		$directory = $app->config[AppConfig::REPOSITORY] . '/files-index';

		return $index ?: $index = new FileStorageIndex($directory);
	}

	/**
	 * @param Application $app
	 *
	 * @return FileStorage
	 */
	static public function get_file_storage(Application $app)
	{
		static $manager;

		$directory = $app->config[AppConfig::REPOSITORY_FILES];

		return $manager ?: $manager = new FileStorage($directory, $app->file_storage_index);
	}
}
