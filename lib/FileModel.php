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

use Icybee\Modules\Nodes\NodeModel;

class FileModel extends NodeModel
{
	const ACCEPT = '#files-accept';
	const UPLOADED = '#files-uploaded';
	const MAX_NORMALIZED_TITLE_LENGTH = 64;

	/**
	 * Makes an absolute path from the specified parameters.
	 *
	 * @param int|null $key
	 * @param string $title
	 * @param string $extension
	 *
	 * @return string
	 */
	static protected function make_absolute_path($key, $title, $extension)
	{
		if ($key)
		{
			$normalized_title = \ICanBoogie\normalize($title);

			if (strlen($normalized_title) > self::MAX_NORMALIZED_TITLE_LENGTH)
			{
				$pos = strrpos($normalized_title, '-', -strlen($normalized_title) + self::MAX_NORMALIZED_TITLE_LENGTH);

				$normalized_title = $pos
					? substr($normalized_title, 0, $pos)
					: substr($normalized_title, 0, self::MAX_NORMALIZED_TITLE_LENGTH);
			}

			$filename = $key . '-' . $normalized_title . $extension;
		}
		else
		{
			$filename = \ICanBoogie\generate_v4_uuid();
		}

		return \ICanBoogie\REPOSITORY . 'files' . DIRECTORY_SEPARATOR . $filename;
	}

	/**
	 * Ensures that a path is absolute.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	static protected function ensure_path_is_absolute($path)
	{
		if (!$path)
		{
			return null;
		}

		$root = \ICanBoogie\DOCUMENT_ROOT;

		if (strpos($path, $root) !== 0)
		{
			$path = $root . ltrim($path, DIRECTORY_SEPARATOR);
		}

		return $path;
	}

	/**
	 * Ensures that a path is relative to the document root.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	static protected function ensure_path_is_relative($path)
	{
		$root = \ICanBoogie\DOCUMENT_ROOT;

		if (strpos($path, $root) === 0)
		{
			$path = substr($path, strlen($root) - 1);
		}

		return $path;
	}

	/**
	 * Ensures that a path is writable.
	 *
	 * @param string $path
	 *
	 * @throws \LogicException if the path is not writable.
	 */
	static protected function ensure_path_is_writable($path)
	{
		$path = self::ensure_path_is_absolute($path);
		$parent = dirname($path);

		if (!is_dir($parent))
		{
			mkdir($parent, 0705, true);
		}

		if (!is_writable($parent))
		{
			throw new \LogicException("The directory is not writable: $parent.");
		}
	}

	/*
	 *
	 */

	/**
	 * @inheritdoc
	 */
	public function save(array $properties, $key = null, array $options = [])
	{
		#
		# because the newly uploaded file might not overwrite the previous file if there extensions
		# don't match, we use the $delete variable to delete the previous file. the variable
		# is defined after an upload.
		#

		$delete = null;

		#
		# $previous_title is used to check if the file has to been renamed.
		# It is set to the last value of the entry, or NULL if we are creating a
		# new one.
		#
		# If needed, the file is renamed after the entry has been saved.
		#

		/* @var $file \ICanBoogie\HTTP\File */

		$file = null;
		$extension = null;
		$previous_path = null; // absolute previous path

		if (isset($properties[File::HTTP_FILE]))
		{
			$file = $properties[File::HTTP_FILE];
			$extension = $file->extension;
			$previous_path = $file->pathname;

			$properties[File::MIME] = $file->type;
			$properties[File::SIZE] = $file->size;

			if (empty($properties[File::TITLE]))
			{
				$properties[File::TITLE] = $file->unsuffixed_name;
			}

			$this->ensure_path_is_writable(self::make_absolute_path(null, uniqid(), $file->extension));
		}

		#
		# If we are modifying an entry, we load its previous values to check for updates related
		# to the title.
		#

		else if ($key)
		{
			$previous_path = $this->ensure_path_is_absolute($this->select('path')->filter_by_nid($key)->rc);
			$extension = pathinfo($previous_path, PATHINFO_EXTENSION);

			if ($extension)
			{
				$extension = '.' . $extension;
			}
		}

		$key = parent::save($properties, $key, $options);

		if (!$key)
		{
			return $key;
		}

		$update_path = self::make_absolute_path($key, $properties[File::TITLE], $extension);

		if ($previous_path != $update_path)
		{
			if ($file)
			{
				$file->move($update_path);
			}
			else
			{
				rename($previous_path, $update_path);
			}

			$this->update([ File::PATH => $this->ensure_path_is_relative($update_path) ], $key);
		}

		return $key;
	}

	/**
	 * @inheritdoc
	 */
	public function delete($key)
	{
		$path = $this->select('path')->filter_by_nid($key)->rc;

		$rc = parent::delete($key);

		if ($rc && $path)
		{
			$root = \ICanBoogie\DOCUMENT_ROOT;

			if (is_file($root . $path))
			{
				unlink($root . $path);
			}
		}

		return $rc;
	}
}
