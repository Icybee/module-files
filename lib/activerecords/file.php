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
 * @property-read string $extension The file extension. If any, the extension includes the dot,
 * e.g. ".zip".
 */
class File extends \Icybee\Modules\Nodes\Node
{
	const PATH = 'path';
	const MIME = 'mime';
	const SIZE = 'size';
	const DESCRIPTION = 'description';

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
	public $description;

	/**
	 * Defaults the model to "files".
	 */
	public function __construct($model='files')
	{
		parent::__construct($model);
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
			return;
		}

		return '.' . $extension;
	}

	public function url($type='view')
	{
		if ($type == 'download')
		{
			return ($this->siteid ? $this->site->path : '') . '/api/' . $this->constructor . '/' . $this->nid . '/download';
		}

		return parent::url($type);
	}
}