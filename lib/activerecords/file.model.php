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

use ICanBoogie\Exception;
use ICanBoogie\Uploaded;

class Model extends \Icybee\Modules\Nodes\Model
{
	const ACCEPT = '#files-accept';
	const UPLOADED = '#files-uploaded';

	public function save(array $properties, $key=null, array $options=array())
	{
		#
		# because the newly uploaded file might not overrite the previous file if there extensions
		# don't match, we use the $delete variable to delete the previous file. the variable
		# is defined after an upload.
		#

		$delete = null;

		#
		# $previous_title is used to check if the file has to been renamed.
		# It is set to the last value of the entry, or NULL if we are creating a
		# new one.
		#
		# If nedded, the file is renamed after the entry has been saved.
		#

		$previous_title = null;
		$previous_path = null;

		#
		# If we are modifying an entry, we load its previous values to check for updates related
		# to the title.
		#

		if ($key)
		{
			#
			# load previous entry to check for changes
			#

			$previous = $this->select('title, path, mime')->filter_by_nid($key)->one;

			#
			# extract previous to obtain previous_title, previous_path and previous_mime
			#

			extract($previous, EXTR_PREFIX_ALL, 'previous');

			$properties[File::MIME] = $previous_mime;
		}

		if (isset($properties[File::HTTP_FILE]))
		{
			/* @var $file \ICanBoogie\HTTP\File */

			$file = $properties[File::HTTP_FILE];

			$delete = $previous_path;
			$path = \ICanBoogie\strip_root($file->pathname);
			$previous_path = $path;
			$previous_title = null; // setting `previous_title` to null forces the update

			$properties[File::MIME] = $file->type;
			$properties[File::SIZE] = $file->size;

			if (empty($properties[File::TITLE]))
			{
				$properties[File::TITLE] = $file->unsuffixed_name;
			}

			$properties[File::PATH] = $path;
		}

		$title = null;

		if (isset($properties[File::TITLE]))
		{
			$title = $properties[File::TITLE];
		}

		$mime = $properties[File::MIME];

		#
		# before we continue, we have to check if we can actually move the file to the repository
		#

		$path = self::make_path($key, $title, $previous_path, $mime);

		$root = \ICanBoogie\DOCUMENT_ROOT;
		$parent = dirname($path);

		if (!is_dir($root . $parent))
		{
			mkdir($root . $parent, 0705, true);
		}

		$key = parent::save($properties, $key, $options);

		if (!$key)
		{
			return $key;
		}

		#
		# change path according to node's title
		#

		if (($path != $previous_path) || (!$previous_title || ($previous_title != $title)))
		{
			$path = self::make_path($key, $title, $previous_path, $mime);

			if ($delete && is_file($root . $delete))
			{
				unlink($root . $delete);
			}

			$ok = rename($root . $previous_path, $root . $path);

			if (!$ok)
			{
				throw new \Exception(\ICanBoogie\format('Unable to rename %previous to %path', [

					'previous' => $previous_path,
					'path' => $path

				]));
			}

			$this->update([ File::PATH => $path ], $key);
		}

		return $key;
	}

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

	static protected function make_path($key, $title, $path, $mime)
	{
		$base = dirname($mime);

		if ($base == 'application')
		{
			$base = basename($mime);
		}

		if (!in_array($base, [ 'image', 'audio', 'pdf', 'zip' ]))
		{
			$base = 'bin';
		}

		$rc = \ICanBoogie\strip_root(\ICanBoogie\REPOSITORY . 'files')
		. '/' . $base . '/' . ($key ?: uniqid()) . '-' . \ICanBoogie\normalize($title);

		#
		# append extension
		#

		$extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'file';

		return $rc . '.' . $extension;
	}
}