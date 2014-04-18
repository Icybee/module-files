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

/**
 * Upload a file to the repository's temporary folder.
 *
 * @property-read \ICanBoogie\HTTP\File $file The uploaded file.
 */
class UploadOperation extends \ICanBoogie\Operation
{
	/**
	 * @var \ICanBoogie\HTTP\File The target file of the operation.
	 */
	protected $file;

	protected function get_file()
	{
		return $this->file;
	}

	/**
	 * @var array Accepted file types.
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
		$this->module->clean_temporary_files();

		return parent::__invoke($request);
	}

	/**
	 * Validates the operation if the file upload succeeded.
	 */
	protected function validate(\ICanboogie\Errors $errors)
	{
		global $core;

		#
		# forces 'application/json' response type
		#

		$_SERVER['HTTP_ACCEPT'] = 'application/json';

		$this->file = $file = $this->request->files['path'];

		if (!$file)
		{
			$errors[SaveOperation::USERFILE] = $errors->format("No file was uploaded.");

			return false;
		}

		$error_message = $file->error_message;

		$max_file_size = $core->registry["{$this->module->flat_id}.max_file_size"];

		if ($max_file_size && $max_file_size < $file->size)
		{
			$error_message = $errors->format("Maximum file size is :size Mb", [ ':size' => round($max_file_size / 1024) ]);
		}

		if (!$file->match($this->accept))
		{
			$error_message = $errors->format("Only the following file types are accepted: %accepted.", [ '%accepted' => implode(', ', $this->accept) ]);
		}

		if ($error_message)
		{
			$errors['path'] = $error_message;
		}

		return true;
	}

	protected function process()
	{
		$file = $this->file;

		$pathname = \ICanBoogie\REPOSITORY . 'tmp' . DIRECTORY_SEPARATOR . uniqid(null, true) . $file->extension;

		$file->move($pathname);

		file_put_contents($pathname . '.info', json_encode($file->to_array()));

		$title = $file->unsuffixed_name;
		$pathname = \ICanBoogie\strip_root($pathname);

		$this->response['infos'] = null;

		if (isset($_SERVER['HTTP_X_USING_FILE_API']))
		{
			$size = \ICanBoogie\I18n\format_size($file->size);

			$this->response['infos'] = <<<EOT
<ul class="details">
	<li><span title="{$pathname}">{$title}</span></li>
	<li>$file->type</li>
	<li>$size</li>
</ul>
EOT;

		}

		return array_merge($file->to_array(), [

			'title' => $title,
			'pathname' => $pathname

		]);
	}
}