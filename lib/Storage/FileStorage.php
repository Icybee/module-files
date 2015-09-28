<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Files\Storage;

use ICanBoogie\Accessor\AccessorTrait;

/**
 * @property-read string $root
 * @property-read FileStorageIndex $index
 */
class FileStorage
{
	use AccessorTrait;

	/**
	 * @var string
	 */
	private $root;

	protected function get_root()
	{
		return $this->root;
	}

	/**
	 * @var FileStorageIndex
	 */
	private $index;

	protected function get_index()
	{
		return $this->index;
	}

	/**
	 * @param string $root
	 * @param FileStorageIndex $index
	 */
	public function __construct($root, FileStorageIndex $index)
	{
		if (!$root)
		{
			throw new \InvalidArgumentException("`\$root` parameter cannot be empty.");
		}

		$this->root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		$this->index = $index;
	}

	/**
	 * Adds a reference.
	 *
	 * @param int $nid
	 * @param string $uuid
	 * @param string $hash
	 *
	 * @return IndexKey
	 */
	public function index($nid, $uuid, $hash)
	{
		$this->assert_hash_is_used($hash);

		$key = IndexKey::from([ $nid, $uuid, $hash ]);

		$this->index->add($key);

		return $key;
	}

	/**
	 * Adds a file.
	 *
	 * @param string $source Absolute path to the source file.
	 *
	 * @return Pathname
	 */
	public function add($source)
	{
		$hash = $this->hash($source);
		$pathname = $this->find_by_hash($hash);

		if ($pathname)
		{
			return $pathname;
		}

		$pathname = $this->create_pathname($source, $hash);

		copy($source, $pathname);

		return $pathname;
	}

	/**
	 * Releases a reference to a file.
	 *
	 * If a file has no reference left it is deleted.
	 *
	 * @param $key_or_id_or_uuid_or_hash
	 */
	public function release($key_or_id_or_uuid_or_hash)
	{
		$index = $this->index;
		$matches = $index->find($key_or_id_or_uuid_or_hash);

		foreach ($matches as $key)
		{
			$index->delete($key);
		}

		foreach ($matches as $key)
		{
			$hash = $key->hash;

			if ($index->find($hash))
			{
				continue;
			}

			$pathname = $this->find_by_hash($hash);

			unlink($pathname);
		}
	}

	/**
	 * Finds a file.
	 *
	 * @param IndexKey|int|string $key_or_nid_or_uuid_or_hash
	 *
	 * @return Pathname|null
	 */
	public function find($key_or_nid_or_uuid_or_hash)
	{
		$matches = $this->index->find($key_or_nid_or_uuid_or_hash);

		if (!$matches)
		{
			return null;
		}

		/* @var $key IndexKey */

		$key = reset($matches);

		return $this->find_by_hash($key->hash);
	}

	/**
	 * Finds a file using its hash.
	 *
	 * @param string $hash A hash from {@link Pathname::hash()}.
	 *
	 * @return Pathname|null
	 */
	protected function find_by_hash($hash)
	{
		$di = new \DirectoryIterator($this->root);
		$di = new \RegexIterator($di, '#^' . preg_quote($hash) . '\-#');

		foreach ($di as $file)
		{
			return Pathname::from($file->getPathname());
		}

		return null;
	}

	/**
	 * Hashes a file.
	 *
	 * The file is hashed with {@link Pathname::hash()}.
	 *
	 * @param string $pathname Absolute pathname to the file.
	 *
	 * @return string The hash of the file.
	 *
	 * @throws \LogicException if the file cannot be hashed.
	 */
	public function hash($pathname)
	{
		return Pathname::hash($pathname);
	}

	/**
	 * Creates hash pathname.
	 *
	 * @param string $pathname
	 * @param string $hash A hash from {@link Pathname::hash()}. If empty a hash is computed from
	 * the file.
	 *
	 * @return Pathname
	 */
	public function create_pathname($pathname, $hash = null)
	{
		$hash = $hash ?: $this->hash($pathname);

		return Pathname::from([ $this->root, $hash ]);
	}

	/**
	 * Asserts that a hash is used by a file.
	 *
	 * @param string $hash
	 */
	protected function assert_hash_is_used($hash)
	{
		if (!$this->find_by_hash($hash))
		{
			throw new \LogicException("No file matches the hash `$hash`.");
		}
	}
}
