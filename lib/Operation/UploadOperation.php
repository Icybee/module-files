<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Files\Operation;

use ICanboogie\Errors;
use ICanBoogie\HTTP\File as HTTPFile;
use ICanBoogie\HTTP\Request;
use ICanBoogie\Operation;

use Icybee\Binding\Core\PrototypedBindings;
use Icybee\Modules\Files\File;
use Icybee\Modules\Files\Module;

/**
 * Upload a file to the repository's temporary folder.
 *
 * @property-read HTTPFile $file The uploaded file.
 * @property Module $module
 * @property \ICanBoogie\Core|\Icybee\Binding\Core\CoreBindings|\Icybee\Modules\Registry\Binding\CoreBindings $app
 */
class UploadOperation extends Operation
{
	use PrototypedBindings;

	/**
	 * @var HTTPFile The target file of the operation.
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
		return [

			self::CONTROL_PERMISSION => Module::PERMISSION_CREATE

		] + parent::get_controls();
	}

	public function action(Request $request)
	{
		$this->module->clean_temporary_files();

		return parent::action($request);
	}

	/**
	 * Validates the operation if the file upload succeeded.
	 *
	 * @inheritdoc
	 */
	protected function validate(Errors $errors)
	{
		#
		# forces 'application/json' response type
		#

		$_SERVER['HTTP_ACCEPT'] = 'application/json';

		$this->file = $file = $this->request->files[File::HTTP_FILE];

		if (!$file)
		{
			$errors->add(SaveOperation::USERFILE, "No file was uploaded.");

			return false;
		}

		$error_message = $file->error_message;

		$max_file_size = $this->app->registry["{$this->module->flat_id}.max_file_size"] * 1024;

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
			$errors[File::HTTP_FILE] = $error_message;
		}

		return true;
	}

	protected function process()
	{
		$file = $this->file;
		$title = $file->unsuffixed_name;
		$type = $file->type;
		$pathname = $this->create_temporary_file($file);
		$relative_pathname = \ICanBoogie\strip_root($pathname);

		$this->response['infos'] = null;

		if (isset($_SERVER['HTTP_X_USING_FILE_API']))
		{
			$size = \ICanBoogie\I18n\format_size($file->size);

			$this->response['infos'] = <<<EOT
<ul class="details">
	<li><span title="{$relative_pathname}">{$title}</span></li>
	<li>$type</li>
	<li>$size</li>
</ul>
EOT;

		}

		return array_merge($file->to_array(), [

			'title' => $title,
			'type' => $type,
			'pathname' => $relative_pathname

		]);
	}

	/**
	 * Creates temporary file with attached information.
	 *
	 * @param HTTPFile $file
	 *
	 * @return string
	 */
	protected function create_temporary_file(HTTPFile $file)
	{
		$pathname = \ICanBoogie\REPOSITORY . 'tmp' . DIRECTORY_SEPARATOR . \ICanBoogie\generate_v4_uuid() . $file->extension;

		$file->move($pathname);

		file_put_contents($pathname . '.info', json_encode($file->to_array()));

		return $pathname;
	}
}
