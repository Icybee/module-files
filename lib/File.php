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
 * @property-read string $short_hash
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
		if (!$this->nid && !$this->uuid)
		{
			return null;
		}

		$pathname = $this->file_storage->find($this->uuid);

		if (!$pathname)
		{
			throw new \LogicException("Unable to retrieve pathname for {$this->uuid}.");
		}

		return $pathname;
	}

	/**
	 * @var string
	 */
	protected $short_hash;

	/**
	 * Returns the short hash of the file.
	 *
	 * @return string
	 */
	protected function get_short_hash()
	{
		return $this->short_hash ?: $this->pathname->short_hash;
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
	 *
	 * @inheritdoc
	 */
	public function save(array $options = [])
	{
		/* @var $file HTTPFile */

		$file = null;

		if (isset($this->{ self::HTTP_FILE }))
		{
			$file = $this->{ self::HTTP_FILE };
			$this->save_file_before($file);
		}

		$rc = parent::save($options);

		if ($rc && $file)
		{
			unset($this->{ self::HTTP_FILE });

			$this->save_file_after($file);
		}

		return $rc;
	}

	public function delete()
	{
		$rc = parent::delete();

		if ($rc)
		{
			$this->file_storage->release($this->uuid);
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
		$this->short_hash = Pathname::short_hash($file->pathname);

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

		$route_id = "{$this->constructor}:$type";

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
