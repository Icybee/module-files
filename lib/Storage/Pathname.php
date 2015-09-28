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
 * Representation of a hash pathname.
 *
 * @property-read string $root Root directory of the managed files.
 * @property-read string $hash A hash from {@link Pathname::hash()}
 * @property-read string $short_hash The first 8 characters of {@link $hash}.
 * @property-read string $random Some random string added to the filename.
 * @property-read string $relative Returns a path relative to {@link \ICanBoogie\DOCUMENT_ROOT}.
 * @property-read string $filename Returns the file name.
 */
class Pathname
{
	use AccessorTrait;

	const HASH_ALGO = 'sha384';
	const HASH_LENGTH = 64;
	const RANDOM_LENGTH = 16;
	const BASE64URL_CHARACTER_CLASS = 'A-Za-z0-9\-_';
	const FILENAME_REGEX = '([A-Za-z0-9\-_]{64})-([A-Za-z0-9\-_]{16})';

	/**
	 * Hash a file into a {@link HASH_LENGTH} character length string.
	 *
	 * @param string $pathname Absolute pathname to the file.
	 *
	 * @return string The hash of the file.
	 *
	 * @throws \LogicException if the file cannot be hashed.
	 */
	static public function hash($pathname)
	{
		$hash = hash_file(self::HASH_ALGO, $pathname, true);

		if (!$hash)
		{
			throw new \LogicException("Unable to hash file: $pathname.");
		}

		return Base64::encode($hash);
	}

	/**
	 * Creates a random base64url encoded 16 characters wide string.
	 *
	 * @return string
	 */
	static public function random()
	{
		return Base64::encode(openssl_random_pseudo_bytes(12));
	}

	/**
	 * @var string
	 */
	private $root;

	/**
	 * @return string
	 */
	protected function get_root()
	{
		return $this->root;
	}

	/**
	 * @var string
	 */
	private $hash;

	/**
	 * @return string
	 */
	protected function get_hash()
	{
		return $this->hash;
	}

	protected function get_short_hash()
	{
		return substr($this->hash, 0, 8);
	}

	/**
	 * @var string
	 */
	private $random;

	/**
	 * @return string
	 */
	protected function get_random()
	{
		return $this->random;
	}

	/**
	 * Returns a path relative to {@link \ICanBoogie\DOCUMENT_ROOT}.
	 *
	 * @return string
	 */
	protected function get_relative()
	{
		return substr((string) $this, strlen(\ICanBoogie\DOCUMENT_ROOT) - 1);
	}

	/**
	 * Returns the file name.
	 *
	 * @return string
	 */
	protected function get_filename()
	{
		return "{$this->hash}-{$this->random}";
	}

	/**
	 * Creates a new {@link Pathname} instance.
	 *
	 * @param string|array $pathname_or_parts The absolute pathname of a hash pathname or an array
	 * with the following values:
	 *
	 * - Root directory of the managed files.
	 * - A hash from {@link Pathname::hash()}.
	 * - A random string from {@link Pathname::random()}, which is created if empty.
	 *
	 * @return static
	 */
	static public function from($pathname_or_parts)
	{
		if ($pathname_or_parts instanceof self)
		{
			return $pathname_or_parts;
		}

		if (is_array($pathname_or_parts))
		{
			return static::from_parts($pathname_or_parts);
		}

		if (is_string($pathname_or_parts))
		{
			return static::from_pathname($pathname_or_parts);
		}

		throw new \InvalidArgumentException("Expected an array or a string, got: " . gettype($pathname_or_parts) . ".");
	}

	/**
	 * Creates a new {@link Pathname} instance from parts.
	 *
	 * @param array $parts An array with the following values:
	 *
	 * - Root directory of the managed files.
	 * - A hash from {@link Pathname::hash()}.
	 * - A random string from {@link Pathname::random()}, which is created if empty.
	 *
	 * @return static
	 */
	static protected function from_parts(array $parts)
	{
		list($root, $hash, $random) = $parts + [ 2 => null];

		$random = $random ?: self::random();

		return new static($root, $hash, $random);
	}

	/**
	 * Creates an instance from a pathname.
	 *
	 * @param string $pathname The absolute pathname of a hash pathname.
	 *
	 * @return static
	 */
	static protected function from_pathname($pathname)
	{
		$ds = preg_quote(DIRECTORY_SEPARATOR);

		if (!preg_match("#(.+{$ds})" . self::FILENAME_REGEX . '$#', $pathname, $matches))
		{
			throw new \InvalidArgumentException("Invalid hash pathname: $pathname.");
		}

		return static::from_parts(array_slice($matches, 1));
	}

	/**
	 * @param string $root Root directory of the managed files.
	 * @param string|null A hash from {@link Pathname::hash()}.
	 * @param string $random A random string from {@link Pathname::random()}.
	 */
	public function __construct($root, $hash, $random)
	{
		$this->root = $root;
		$this->hash = $hash;
		$this->random = $random;
	}

	/**
	 * Returns the hash pathname.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->root . $this->filename;
	}
}
