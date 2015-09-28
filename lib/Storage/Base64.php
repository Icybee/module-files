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

/**
 * Base64 support.
 */
class Base64
{
	/**
	 * Encodes data as base64url.
	 *
	 * @param string $data
	 *
	 * @return string
	 */
	static public function encode($data)
	{
		return strtr(base64_encode($data), [ '+' => '-', '/' => '_' ]);
	}

	/**
	 * Decodes base64url data.
	 *
	 * @param string $data
	 *
	 * @return string
	 */
	static public function decode($data)
	{
		return base64_decode(strtr($data, [ '-' => '+', '_' => '/' ]));
	}

	/**
	 * Encodes data as base64url, removes padding character.
	 *
	 * @param string $data
	 *
	 * @return string
	 */
	static public function encode_unpadded($data)
	{
		return rtrim(self::encode($data), '=');
	}

	/**
	 * Decodes unpadded base64url.
	 *
	 * @param string $data
	 *
	 * @return string
	 */
	static public function decode_unpadded($data)
	{
		return self::decode($data);
	}
}
