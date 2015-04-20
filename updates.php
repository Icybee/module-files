<?php

namespace Icybee\Modules\Files;

use ICanBoogie\Updater\AssertionFailed;
use ICanBoogie\Updater\Update;

/**
 * - Rename table `resources_files` as `files`.
 *
 * @module files
 */
class Update20120101 extends Update
{
	public function update_table_forms()
	{
		$db = $this->app->db;

		if (!$db->table_exists('resources_files'))
		{
			throw new AssertionFailed('assert_table_exists', 'resources_files');
		}

		$db("RENAME TABLE `resources_files` TO `files`");
	}

	public function update_constructor_type()
	{
		$db = $this->app->db;
		$db("UPDATE nodes SET constructor = 'files' WHERE constructor = 'resources.files'");
	}
}
