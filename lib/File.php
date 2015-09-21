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

use ICanBoogie\HTTP\File as HTTPFile;

use Icybee\Binding\Core\PrototypedBindings;
use Icybee\Modules\Files\Storage\Pathname;
use Icybee\Modules\Nodes\Node;

/**
 * Representation of a managed file.
 *
 * @property-read \ICanBoogie\Core|\Icybee\Binding\Core\CoreBindings|Binding\CoreBindings $app
 * @property-read Storage\FileStorage $file_storage
 * @property-read Pathname $pathname Absolute path to the file.
 */
class File extends Node
{
	use PrototypedBindings;

	const MODEL_ID = 'files';

	const MIME = 'mime';
	const SIZE = 'size';
	const EXTENSION = 'extension';
	const DESCRIPTION = 'description';
	const HTTP_FILE = 'file';

	/**
	 * Size of the file.
	 *
	 * @var int
	 */
	public $size;

	/**
	 * MIME type of the file.
	 *
	 * @var string
	 */
	public $mime;

	/**
	 * File extension, including the dot ".".
	 *
	 * @var string
	 */
	public $extension = '';

	/**
	 * Description of the file.
	 *
	 * @var string
	 */
	public $description = '';

	/**
	 * @return Pathname|null
	 */
	protected function get_pathname()
	{
		return $this->file_storage->find($this->uuid);
	}

	/**
	 * @return Storage\FileStorage
	 */
	protected function get_file_storage()
	{
		return $this->app->file_storage;
	}

	/**
	 * If {@link HTTP_FILE} is defined, the {@link HTTPFile} instance is used to
	 * set the {@link $mime}, {@link $size} and {@link $extension} properties.
	 * The {@link $title} property is updated as well if it is empty.
	 *
	 * After the record is saved, the {@link HTTP_FILE} property is removed.
	 */
	public function save()
	{
		/* @var $file HTTPFile */

		$file = null;

		if (isset($this->{ self::HTTP_FILE }))
		{
			$file = $this->{ self::HTTP_FILE };
			$this->save_file_before($file);
		}

		$rc = parent::save();

		if ($rc && $file)
		{
			unset($this->{ self::HTTP_FILE });

			$this->save_file_after($file);
		}

		return $rc;
	}

	/**
	 * Begins saving the HTTP file.
	 *
	 * @param HTTPFile $file
	 */
	protected function save_file_before(HTTPFile $file)
	{
		$this->mime = $file->type;
		$this->size = $file->size;
		$this->extension = $file->extension;

		if (!$this->title)
		{
			$this->title = $file->unsuffixed_name;
		}
	}

	/**
	 * Finishes saving the HTTP file.
	 *
	 * @param HTTPFile $file
	 */
	protected function save_file_after(HTTPFile $file)
	{
		$storage = $this->file_storage;
		$pathname = $storage->create_pathname($file->pathname);

		if (!file_exists($pathname))
		{
			$file->move($pathname);
		}

		$storage->index($this->nid, $this->uuid, $pathname->hash);
	}

	/**
	 * Returns a URL for this record.
	 *
	 * @param string $type
	 *
	 * @return \ICanBoogie\Routing\FormattedRoute|string
	 */
	public function url($type = 'view')
	{
		$routes = $this->app->routes;
		$route_id = "{$this->constructor}/$type";

		if (isset($routes[$route_id]))
		{
			return $routes[$route_id]->format($this);
		}

		$route_id = "files:$type";

		if (isset($routes[$route_id]))
		{
			return $routes[$route_id]->format($this);
		}

		return parent::url($type);
	}
}
