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

use ICanBoogie\Updater\AssertionFailed;
use ICanBoogie\Updater\Update;
use Icybee\Modules\Files\Storage\IndexKey;
use Icybee\Modules\Files\Storage\Pathname;

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

		$db("RENAME TABLE `{prefix}resources_files` TO `files`");
	}

	public function update_constructor_type()
	{
		$db = $this->app->db;
		$db("UPDATE {prefix}nodes SET constructor = 'files' WHERE constructor = 'resources.files'");
	}
}

/**
 * - Add column `extension`.
 *
 * @module files
 *
 * @property \ICanBoogie\Core|Binding\ApplicationBindings $app
 */
class Update20150902 extends Update
{
	public function update_column_extension()
	{
		$model = $this->module->model;
		$model
			->assert_not_has_column('extension')
			->create_column('extension');

		$update = $model->prepare("UPDATE {self} SET extension = ? WHERE nid = ?");

		foreach ($model->select("nid, path")->mode(\PDO::FETCH_NUM) as list($nid, $path))
		{
			$update('.' . pathinfo($path, PATHINFO_EXTENSION), $nid);
		}
	}

	public function update_storage_index()
	{
		$model = $this->module->model;
		$model->assert_has_column('path');

		$storage = $this->app->file_storage;
		$index = $this->app->file_storage_index;
		$root = $index->root;

		if (!file_exists($root))
		{
			mkdir($root, 0705);
		}

		$document_root = rtrim(\ICanBoogie\DOCUMENT_ROOT, DIRECTORY_SEPARATOR);

		foreach ($model->select("nid, uuid, path")->mode(\PDO::FETCH_NUM) as list($nid, $uuid, $path))
		{
			$path = $document_root . $path;

			if (!file_exists($path))
			{
				echo "!! file $path does not exists.\n";

				continue;
			}

			$hash = $storage->add($path)->hash;
			$index->add(IndexKey::from([ $nid, $uuid, $hash ]));
		}

		$model->remove_column('path');
	}
}

/**
 * - Add column `short_hash`.
 *
 * @module files
 *
 * @property \ICanBoogie\Core|Binding\ApplicationBindings $app
 */
class Update20160709 extends Update
{
	public function update_column_short_hash()
	{
		$model = $this->module->model;
		$model
			->assert_not_has_column('short_hash')
			->create_column('short_hash');

		$update = $model->prepare("UPDATE {self} SET short_hash = ? WHERE nid = ?");

		foreach ($model->all as $record)
		{
			$update($record->pathname->short_hash, $record->nid);
		}
	}
}
