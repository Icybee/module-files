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
 * Representation of an index key.
 *
 * @property-read int $nid Record identifier.
 * @property-read string $formatted_nid `$nid` formatted as a composite part.
 * @property-read string $uuid Record v4 UUID.
 * @property-read string $hash A hash from {@link Pathname::hash()}
 */
class IndexKey
{
	use AccessorTrait;

	const HEXNID_LENGTH = 16;
	const UUID_LENGTH = 36;
	const HASH_LENGTH = Pathname::HASH_LENGTH;

	/**
	 * @var IndexKey[]
	 */
	static private $instances = [];

	/**
	 * Creates a {@link IndexKey} instance from a composite key string or array.
	 *
	 * @param string|array $composite_or_array A composite key string or array.
	 *
	 * @return static
	 */
	static public function from($composite_or_array)
	{
		$composite = $composite_or_array;
		$array = $composite_or_array;

		if (is_array($composite_or_array))
		{
			$composite = self::format_key($composite_or_array[0], $composite_or_array[1], $composite_or_array[2]);
		}

		if (isset(self::$instances[$composite]))
		{
			return self::$instances[$composite];
		}

		if (is_string($composite_or_array))
		{
			$array = self::parse_key($composite);
		}

		return self::$instances[$composite] = new static($array[0], $array[1], $array[2]);
	}

	/**
	 * Parse index key string.
	 *
	 * @param string $key
	 *
	 * @return array
	 */
	static private function parse_key($key)
	{
		$nid = hexdec(substr($key, 0, self::HEXNID_LENGTH));
		$uuid = substr($key, self::HEXNID_LENGTH + 1, self::UUID_LENGTH);
		$hash = substr($key, self::HEXNID_LENGTH + 1 + self::UUID_LENGTH + 1);

		return [ $nid, $uuid, $hash ];
	}

	/**
	 * Formats a composite key.
	 *
	 * @param int $nid
	 * @param string $uuid
	 * @param string $hash
	 *
	 * @return string
	 */
	static private function format_key($nid, $uuid, $hash)
	{
		$formatted_nid = self::format_nid($nid);

		return "{$formatted_nid}-{$uuid}-{$hash}";
	}

	/**
	 * Formats a node identifier has a composite part.
	 *
	 * @param int $nid
	 *
	 * @return string
	 */
	static public function format_nid($nid)
	{
		return sprintf('%0' . self::HEXNID_LENGTH . 'x', $nid);
	}

	/**
	 * @var int
	 */
	private $nid;

	protected function get_nid()
	{
		return $this->nid;
	}

	protected function get_formatted_nid()
	{
		return self::format_nid($this->nid);
	}

	/**
	 * @var string
	 */
	private $uuid;

	protected function get_uuid()
	{
		return $this->uuid;
	}

	/**
	 * @var string
	 */
	private $hash;

	protected function get_hash()
	{
		return $this->hash;
	}

	/**
	 * @param int $nid
	 * @param string $uuid
	 * @param string $hash
	 */
	public function __construct($nid, $uuid, $hash)
	{
		$this->nid = $nid;
		$this->uuid = $uuid;
		$this->hash = $hash;
	}

	/**
	 * Returns a formatted composite key.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return self::format_key($this->nid, $this->uuid, $this->hash);
	}
}
