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

use Icybee\Modules\Nodes\Node;

/**
 * Representation of a managed file.
 *
 * @property-read string $extension The file extension. If any, the extension includes the dot,
 * e.g. ".zip".
 */
class File extends Node
{
	const MODEL_ID = 'files';

	const PATH = 'path';
	const MIME = 'mime';
	const SIZE = 'size';
	const DESCRIPTION = 'description';
	const HTTP_FILE = 'file';

	/**
	 * Path of the file, relative to the DOCUMENT_ROOT.
	 *
	 * @var string
	 */
	public $path;

	/**
	 * MIME type of the file.
	 *
	 * @var string
	 */
	public $mime;

	/**
	 * Size of the file.
	 *
	 * @var int
	 */
	public $size;

	/**
	 * Description of the file.
	 *
	 * @var string
	 */
	public $description = '';

	/**
	 * If {@link HTTP_FILE} is defined, the {@link \ICanBoogie\HTTP\File} instance is used to
	 * set the {@link $mime} and {@link $size} properties, as well as the {@link $title} property
	 * if it is empty.
	 *
	 * After the record is saved, the {@link HTTP_FILE} property is removed. Also, the
	 * {@link $path} property is updated.
	 */
	public function save()
	{
		if (isset($this->{ self::HTTP_FILE }))
		{
			/* @var $file \ICanBoogie\HTTP\File */

			$file = $this->{ self::HTTP_FILE };

			$this->mime = $file->type;
			$this->size = $file->size;

			if (!$this->title)
			{
				$this->title = $file->unsuffixed_name;
			}
		}

		$rc = parent::save();

		unset($this->{ self::HTTP_FILE });

		if ($rc)
		{
			$this->path = $this->model->select(self::PATH)->filter_by_nid($rc)->rc;
		}

		return $rc;
	}

	/**
	 * Returns the extension of the file.
	 *
	 * Note: The dot is included e.g. ".zip".
	 *
	 * @return string
	 */
	protected function get_extension()
	{
		$extension = pathinfo($this->path, PATHINFO_EXTENSION);

		if (!$extension)
		{
			return '';
		}

		return '.' . $extension;
	}

	public function url($type='view')
	{
		$routes = $this->app->routes;
		$route_id = "{$this->constructor}/$type";

		if (isset($routes[$route_id]))
		{
			return $routes[$route_id]->format($this);
		}

		$route_id = "files/$type";

		if (isset($routes[$route_id]))
		{
			return $routes[$route_id]->format($this);
		}

		return parent::url($type);
	}
}
