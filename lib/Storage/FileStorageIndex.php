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
 * Am index of the managed files.
 *
 * @property-read string $root
 */
class FileStorageIndex
{
	use AccessorTrait;

	const MATCH_BY_KEY = 1;
	const MATCH_BY_ID = 2;
	const MATCH_BY_UUID = 3;
	const MATCH_BY_HASH = 4;

	/**
	 * Resolves matching type.
	 *
	 * @param IndexKey|int|string $key_or_id_or_uuid_or_hash A {@link IndexKey}, a node
	 * identifier, a v4 UUID, or a hash.
	 *
	 * @return int
	 *
	 * @throws \InvalidArgumentException if `$key_or_id_or_uuid_or_hash` is not one of
	 * the required type.
	 */
	static private function resolve_matching_type($key_or_id_or_uuid_or_hash)
	{
		if ($key_or_id_or_uuid_or_hash instanceof IndexKey)
		{
			return self::MATCH_BY_KEY;
		}

		if (is_numeric($key_or_id_or_uuid_or_hash))
		{
			return self::MATCH_BY_ID;
		}

		if (strlen($key_or_id_or_uuid_or_hash) === IndexKey::UUID_LENGTH)
		{
			return self::MATCH_BY_UUID;
		}

		if (strlen($key_or_id_or_uuid_or_hash) === IndexKey::HASH_LENGTH)
		{
			return self::MATCH_BY_HASH;
		}

		throw new \InvalidArgumentException("Expected IndexKey instance, node identifier, v4 UUID, or a hash. Got: $key_or_id_or_uuid_or_hash");
	}

	/**
	 * Root directory including a trailing `DIRECTORY_SEPARATOR`.
	 *
	 * @var string
	 */
	private $root;

	protected function get_root()
	{
		return $this->root;
	}

	/**
	 * @param string $root FileStorageIndex root directory.
	 */
	public function __construct($root)
	{
		$this->root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Adds a key to the index.
	 *
	 * Composite keys using the same `uid` or `uuid` are replaced by this one.
	 *
	 * @param IndexKey $key
	 */
	public function add(IndexKey $key)
	{
		touch($this->root . $key);

		foreach ($this->find_by_id($key->id) as $match)
		{
			if ($match === $key)
			{
				continue;
			}

			$this->delete($match);
		}

		foreach ($this->find_by_uuid($key->uuid) as $match)
		{
			if ($match === $key)
			{
				continue;
			}

			$this->delete($match);
		}
	}

	/**
	 * Deletes key(s) from the index.
	 *
	 * @param IndexKey|int|string $key_or_id_or_uuid_or_hash
	 *
	 * @throws \InvalidArgumentException if `$key_or_id_or_uuid_or_hash` is not of the expected type.
	 */
	public function delete($key_or_id_or_uuid_or_hash)
	{
		$matching = $this->find($key_or_id_or_uuid_or_hash);

		if (!$matching)
		{
			return;
		}

		foreach ($matching as $match)
		{
			unlink($this->root . $match);
		}
	}

	/**
	 * Finds composite keys.
	 *
	 * @param IndexKey|int|string $key_or_id_or_uuid_or_hash
	 *
	 * @return IndexKey[]
	 *
	 * @throws \InvalidArgumentException if `$key_or_id_or_uuid_or_hash` is not of the expected type.
	 */
	public function find($key_or_id_or_uuid_or_hash)
	{
		static $methods = [

			self::MATCH_BY_KEY => 'key',
			self::MATCH_BY_ID => 'id',
			self::MATCH_BY_UUID => 'uuid',
			self::MATCH_BY_HASH => 'hash'

		];

		$type = $methods[self::resolve_matching_type($key_or_id_or_uuid_or_hash)];

		return $this->{ 'find_by_' . $type }($key_or_id_or_uuid_or_hash);
	}

	/**
	 * Returns the composite key matching the composite key.
	 *
	 * @param IndexKey|string $key
	 *
	 * @return IndexKey[]
	 */
	protected function find_by_key($key)
	{
		$pathname = $this->root . $key;

		return file_exists($pathname) ? [ $key ] : null;
	}

	/**
	 * Returns the composite keys match an identifier.
	 *
	 * @param int $id The node identifier to match.
	 *
	 * @return IndexKey[]
	 */
	protected function find_by_id($id)
	{
		return $this->matching('#^' . preg_quote(IndexKey::encode_id($id)) . '\-#');
	}

	/**
	 * Returns the composite keys match a v4 UUID.
	 *
	 * @param string $encoded_uuid The UUID to match.
	 *
	 * @return IndexKey[]
	 */
	protected function find_by_encoded_uuid($encoded_uuid)
	{
		return $this->matching("#^.{" . IndexKey::ENCODED_ID_LENGTH . '}\-' . preg_quote($encoded_uuid) . '\-#');
	}

	/**
	 * Returns the composite keys match a v4 UUID.
	 *
	 * @param string $uuid The UUID to match.
	 *
	 * @return IndexKey[]
	 */
	protected function find_by_uuid($uuid)
	{
		return $this->find_by_encoded_uuid(IndexKey::encode_uuid($uuid));
	}

	/**
	 * Returns the composite keys match a hash.
	 *
	 * @param string $hash A hash from {@link Pathname::hash()}
	 *
	 * @return IndexKey[]
	 */
	protected function find_by_hash($hash)
	{
		return $this->matching("#^.{" . IndexKey::ENCODED_ID_LENGTH . '}\-.{' . IndexKey::ENCODED_UUID_LENGTH . '}\-' . preg_quote($hash) . '#');
	}

	/**
	 * Returns the composite keys matching a pattern.
	 *
	 * @param string $pattern The pattern to match.
	 *
	 * @return IndexKey[]
	 */
	protected function matching($pattern)
	{
		$matches = [];

		$di = new \DirectoryIterator($this->root);
		$di = new \RegexIterator($di, $pattern);

		foreach ($di as $match)
		{
			$matches[] = IndexKey::from($match->getFilename());
		}

		return $matches;
	}
}
