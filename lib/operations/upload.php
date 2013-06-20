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

use ICanBoogie\HTTP\Request;
use ICanBoogie\Uploaded;

class UploadOperation extends \ICanBoogie\Operation
{
	/**
	 * The target file of the operation.
	 *
	 * @var Uploaded
	 */
	protected $file;

	/**
	 * Accepted file types.
	 *
	 * @var array
	 */
	protected $accept;

	/**
	 * Controls for the operation: permission(create).
	 */
	protected function get_controls()
	{
		return array
		(
			self::CONTROL_PERMISSION => Module::PERMISSION_CREATE
		)

		+ parent::get_controls();
	}

	public function __invoke(Request $request)
	{
		$this->module->clean_repository();

		return parent::__invoke($request);
	}

	/**
	 * Validates the operation if the file upload succeeded.
	 */
	protected function validate(\ICanboogie\Errors $errors)
	{
		#
		# forces 'application/json' response type
		#

		$_SERVER['HTTP_ACCEPT'] = 'application/json';

		#
		# TODO-20100624: we use 'Filedata' because it's used by Swiff.Uploader. We need to change
		# that as soon as possible.
		#

		$file = new Uploaded('path', $this->accept, true);

		$this->file = $file;
		$this->response['file'] = $file;

		if ($file->er)
		{
			$errors['file'] = $file->er_message;

			return false;
		}

		return true;
	}

	protected function process()
	{
		global $core;

		$file = $this->file;
		$filename = basename($file->location) . $file->extension;
		$path = $core->config['repository.temp'] . '/' . $filename;

		$file->move(\ICanBoogie\DOCUMENT_ROOT . $path, true);

		$name = $file->name;

		$this->response['infos'] = null;
		$this->response['properties'] = array
		(
			'title' => $name
		);

		$size = \ICanBoogie\I18n\format_size($file->size);

		$this->response['infos'] = <<<EOT
<ul class="details">
	<li><span title="Path: {$file->location}">{$name}</span></li>
	<li>$file->mime</li>
	<li>$size</li>
</ul>
EOT;

		return $filename;
	}
}