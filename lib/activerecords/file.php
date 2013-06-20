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

/**
 * Representation of a managed file.
 *
 * @property-read string $extension The file extension.
 * @property-read string $hexnid The nomalized hexadecimal representation fo the record identifier.
 * @property-read string $long_hash The long hash of the record.
 * @property-read string $server_path The absolute path of the file on the server.
 */
class File extends \Icybee\Modules\Nodes\Node
{
	const HASH = 'hash';
	const PATH = 'path';
	const MIME = 'mime';
	const SIZE = 'size';
	const DESCRIPTION = 'description';

	/**
	 * Creates an hash for the specified file.
	 *
	 * @param string $path Absolute path to the file.
	 *
	 * @return string A 48 character long hash.
	 */
	static public function create_hash($path)
	{
		return sha1_file($path) . sprintf('%08x', filesize($path));
	}

	/**
	 * Resolve the path of a file using its hash.
	 *
	 * @param string $hash Hash of the file, provided by {@link create_hash()}.
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return string The absolute path of the file on the server.
	 */
	static public function resolve_path($hash)
	{
		$path = \ICanBoogie\REPOSITORY . 'files' . DIRECTORY_SEPARATOR . $hash;

		$files = glob($path . '.*');

		if ($files)
		{
			return $files[0];
		}

		throw new \InvalidArgumentException("There is no file with the hash <q>$hash</q>.");
	}

	/**
	 * Hash of the file.
	 *
	 * @var string
	 */
	public $hash;

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
	public $description;

	/**
	 * Defaults the model to "files".
	 */
	public function __construct($model='files')
	{
		parent::__construct($model);
	}

	/**
	 * Returns the normalized hexadecimal representation of the record identifier.
	 *
	 * @return string
	 */
	protected function volatile_get_hexnid()
	{
		return sprintf('%08x', $this->nid);
	}

	/**
	 * Returns the long hash of the record.
	 *
	 * The long hash of the record is made of the identifier of the record and the hash of the
	 * file.
	 *
	 * @return string
	 */
	protected function volatile_get_long_hash()
	{
		return "{$this->hexnid}-{$this->hash}";
	}

	/**
	 * Returns the extension of the file.
	 *
	 * Note: The dot is included e.g. ".zip".
	 *
	 * @return string
	 */
	protected function volatile_get_extension()
	{
		return '.' . pathinfo($this->server_path, PATHINFO_EXTENSION);
	}

	/**
	 * Returns the absolute path of the file on the server.
	 *
	 * @return string
	 */
	protected function volatile_get_server_path()
	{
		return self::resolve_path($this->hash);
	}

	/**
	 * Alias for `$this->url('get')`.
	 *
	 * @return string
	 */
	protected function volatile_get_path()
	{
		return $this->url('get');
	}

	public function url($type='view')
	{
		if ($type == 'get')
		{
			return \ICanBoogie\Operation::encode($this->constructor . '/' . $this->long_hash);
		}
		else if ($type == 'download')
		{
			return $this->url('get') . '/download';
		}

		return parent::url($type);
	}
}