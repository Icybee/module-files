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

use ICanBoogie\ErrorCollection;

class Module extends \Icybee\Modules\Nodes\Module
{
	const OPERATION_UPLOAD = 'upload';

	/**
	 * Creates following directories, with their .htaccess file.
	 *
	 * - "/repository/tmp", allow from all
	 * - "/repository/files", allow from all
	 * - "/repository/files-index", deny from all
	 *
	 * @inheritdoc
	 */
	public function install(ErrorCollection $errors)
	{
		$repository = \ICanBoogie\REPOSITORY;

		#
		# $repository/tmp
		#

		$path = $repository . 'tmp';

		if (!file_exists($path))
		{
			$parent = dirname($path);

			if (is_writable($parent))
			{
				mkdir($path);

				file_put_contents($path . DIRECTORY_SEPARATOR . '.htaccess', 'Deny from all');
			}
			else
			{
				$errors->add($this->id, "Unable to create %directory directory, its parent is not writable.", [ '%directory' => $path ]);
			}
		}

		#
		# $repository/files
		#

		$path = $repository . 'files';

		if (!file_exists($path))
		{
			$parent = dirname($path);

			if (is_writable($parent))
			{
				mkdir($path);

				file_put_contents($path . DIRECTORY_SEPARATOR . '.htaccess', 'Allow from all');
			}
			else
			{
				$errors->add($this->id, "Unable to create %directory directory, its parent is not writable", [ '%directory' => $path ]);
			}
		}

		#
		# $repository/files-index
		#

		$path = $repository . 'files-index';

		if (!file_exists($path))
		{
			$parent = dirname($path);

			if (is_writable($parent))
			{
				mkdir($path);

				file_put_contents($path . DIRECTORY_SEPARATOR . '.htaccess', 'Deny from all');
			}
			else
			{
				$errors->add($this->id, 'Unable to create %directory directory, its parent is not writable', [ '%directory' => $path ]);
			}
		}

		#
		# config: max_file_size
		#

		$this->app->registry["{$this->flat_id}.max_file_size"] = 16000;

		return parent::install($errors);
	}

	/**
	 * Checks that the "tmp" and "files" directories exist in the repository.
	 *
	 * @inheritdoc
	 */
	public function is_installed(ErrorCollection $errors)
	{
		$repository = \ICanBoogie\REPOSITORY;

		#
		# $repository/tmp
		#

		$path = $repository . 'tmp';

		if (!is_dir($path))
		{
			$errors->add($this->id, "The %directory directory is missing.", [ '%directory' => $path ]);
		}

		#
		# $repository/files
		#

		$path = $repository . 'files';

		if (!is_dir($path))
		{
			$errors->add($this->id, "The %directory directory is missing.", [ '%directory' => $path ]);
		}

		return parent::is_installed($errors);
	}

	public function clean_temporary_files($lifetime = 3600)
	{
		$path = \ICanBoogie\REPOSITORY . 'tmp';

		if (!is_dir($path))
		{
			\ICanBoogie\log_error('The directory %directory does not exists', [ '%directory' => $path ]);

			return;
		}

		if (!is_writable($path))
		{
			\ICanBoogie\log_error('The directory %directory is not writable', [ '%directory' => $path ]);

			return;
		}

		$dh = opendir($path);

		if (!$dh)
		{
			return;
		}

		$now = time();
		$location = getcwd();

		chdir($path);

		while ($file = readdir($dh))
		{
			if ($file{0} == '.')
			{
				continue;
			}

			$stat = stat($file);

			if ($now - $stat['ctime'] > $lifetime)
			{
				unlink($file);

				\ICanBoogie\log('The temporary file %file has been deleted form the repository %directory', [

					'%file' => $file,
					'%directory' => $path

				]);
			}
		}

		chdir($location);

		closedir($dh);
	}
}
