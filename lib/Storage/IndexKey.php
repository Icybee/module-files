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
 * @property-read int $id Record identifier.
 * @property-read string $uuid Record v4 UUID.
 * @property-read string $hash A hash from {@link Pathname::hash()}
 * @property-read string $encoded_id Encoded `$id`.
 * @property-read string $encoded_uuid Encoded `uuid`.
 */
class IndexKey
{
	use AccessorTrait;

	const UUID_LENGTH = 36;
	const ENCODED_ID_LENGTH = 16;
	const ENCODED_UUID_LENGTH = 22;
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

		if (is_array($composite_or_array))
		{
			list($id, $uuid, $hash) = $composite_or_array;

			return static::from(self::format_key(self::encode_id($id), self::encode_uuid($uuid), $hash));
		}

		if (isset(self::$instances[$composite]))
		{
			return self::$instances[$composite];
		}

		list($encoded_id, $encoded_uuid, $hash) = self::parse_key($composite);

		return self::$instances[$composite] = new static($encoded_id, $encoded_uuid, $hash);
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
		$encoded_id = substr($key, 0, self::ENCODED_ID_LENGTH);
		$encoded_uuid = substr($key, self::ENCODED_ID_LENGTH + 1, self::ENCODED_UUID_LENGTH);
		$hash = substr($key, self::ENCODED_ID_LENGTH + 1 + self::ENCODED_UUID_LENGTH + 1);

		return [ $encoded_id, $encoded_uuid, $hash ];
	}

	/**
	 * Formats a composite key.
	 *
	 * @param string $encoded_id
	 * @param string $encoded_uuid
	 * @param string $hash
	 *
	 * @return string
	 */
	static private function format_key($encoded_id, $encoded_uuid, $hash)
	{
		return "{$encoded_id}-{$encoded_uuid}-{$hash}";
	}

	/**
	 * Encodes identifier as a key part.
	 *
	 * @param int $id
	 *
	 * @return string
	 */
	static public function encode_id($id)
	{
		return sprintf('%0' . self::ENCODED_ID_LENGTH . 'x', $id);
	}

	/**
	 * Decodes identifier encoded by {@link self::encode_id()}.
	 *
	 * @param string $encoded_id
	 *
	 * @return number
	 */
	static public function decode_id($encoded_id)
	{
		return hexdec($encoded_id);
	}

	/**
	 * Encodes a UUID as a key part.
	 *
	 * @param $uuid
	 *
	 * @return string
	 */
	static public function encode_uuid($uuid)
	{
		if (preg_match('/[^0-9a-f\-]/', $uuid))
		{
			throw new \LogicException("Invalid UUID: $uuid.");
		}

		return Base64::encode_unpadded(hex2bin(strtr($uuid, [ '-' => '' ])));
	}

	/**
	 * Decodes UUID encoded by {@link self::encode_uuid()}.
	 *
	 * @param string $encoded_uuid
	 *
	 * @return string
	 */
	static public function decode_uuid($encoded_uuid)
	{
		$data = Base64::decode_unpadded($encoded_uuid);
		$data = bin2hex($data);

		return implode('-', str_split($data, 4));
	}

	/**
	 * @var int
	 */
	private $encoded_id;

	protected function get_encoded_id()
	{
		return $this->encoded_id;
	}

	protected function get_id()
	{
		return self::decode_id($this->encoded_id);
	}

	/**
	 * @var string
	 */
	private $encoded_uuid;

	protected function get_encoded_uuid()
	{
		return $this->encoded_uuid;
	}

	protected function get_uuid()
	{
		return self::decode_uuid($this->encoded_uuid);
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
	 * @param string $encoded_id An encoded identifier as returned by {@link self::encode_id()}
	 * @param string $encoded_uuid A UUID as encoded by {@link self::encode_uid()}
	 * @param string $hash A hash as returned by {@link Pathname::hash()}
	 */
	private function __construct($encoded_id, $encoded_uuid, $hash)
	{
		$this->encoded_id = $encoded_id;
		$this->encoded_uuid = $encoded_uuid;
		$this->hash = $hash;
	}

	/**
	 * Returns a formatted composite key.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return self::format_key($this->encoded_id, $this->encoded_uuid, $this->hash);
	}
}
