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
use ICanBoogie\strip_root;

/**
 * Save a file.
 *
 * @property-read \ICanBoogie\HTTP\File|null $file The file associated with the request.
 */
class SaveOperation extends \Icybee\Modules\Nodes\SaveOperation
{
	/**
	 * Name of the _userfile_ slot.
	 *
	 * @var string
	 */
	const USERFILE = 'path';

	/**
	 * @var \ICanBoogie\HTTP\File|bool The optional file to save with the record.
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
	 * {@inheritdoc}
	 *
	 * Unset {@link File::PATH} because only {@link File::HTTP_FILE} can be used to update it.
	 * {@link File::HTTP_FILE} is updated if {@link $file} is not empty.
	 *
	 * Also, {@link File::DESCRIPTION} is set to and empty string if it is not defined.
	 */
	protected function lazy_get_properties()
	{
		$properties = parent::lazy_get_properties() + [

			'description' => ''

		];

		unset($properties[File::PATH]);

		if ($this->file)
		{
			$properties[File::HTTP_FILE] = $this->file;
		}

		return $properties;
	}

	/**
	 * {@inheritdoc}
	 *
	 * The temporary files stored in the repository are cleaned before the operation is processed.
	 */
	public function __invoke(Request $request)
	{
		$this->module->clean_temporary_files();

		return parent::__invoke($request);
	}

	/**
	 * If PATH is not defined, we check for a file upload, which is required if the operation key
	 * is empty. If a file upload is found, the Uploaded object is set as the operation `file`
	 * property, and the PATH parameter of the operation is set to the file location.
	 *
	 * Note that if the upload is not required - because the operation key is defined for updating
	 * an entry - the PATH parameter of the operation is set to TRUE to avoid error reporting from
	 * the form validation.
	 *
	 * TODO: maybe this is not ideal, since the file upload should be made optional when the form
	 * is generated to edit existing entries.
	 */
	protected function control(array $controls)
	{
		$request = $this->request;

		$path = $request[File::PATH];
		$file = null;

		/* @var $file \ICanBoogie\HTTP\File */

		$file = $request->files[self::USERFILE];

		if ($file && $file->is_valid)
		{
			$filename = strtr(uniqid(null, true), '.', '-') . $file->extension;
			$pathname = \ICanBoogie\REPOSITORY . 'tmp' . DIRECTORY_SEPARATOR . $filename;

			$file->move($pathname);
		}
		else if ($path && strpos($path, \ICanBoogie\strip_root(\ICanBoogie\REPOSITORY . "files")) !== 0)
		{
			$file = $this->resolve_request_file_from_pathname($path);

			if (!$file)
			{
				$this->response->errors[File::PATH] = $this->response->errors->format("Invalid or delete file: %pathname", [ 'pathname' => $path ]);
			}
		}

		unset($request[File::PATH]);
		unset($request[File::MIME]);
		unset($request[File::SIZE]);

		$this->file = $file;

		if ($file)
		{
			#
			# This is used during form validation.
			#

			$request[File::PATH] = $file->pathname;
		}

		return parent::control($controls);
	}

	/**
	 * The method validates unless there was an error during the file upload.
	 */
	protected function validate(\ICanboogie\Errors $errors)
	{
		$file = $this->file;

		if ($file)
		{
			$error_message = $file->error_message;

			$max_file_size = $this->app->registry["{$this->module->flat_id}.max_file_size"] * 1024;

			if ($max_file_size && $max_file_size < $file->size)
			{
				$error_message = $errors->format("Maximum file size is :size Mb", [ ':size' => round($max_file_size / 1024) ]);
			}

			if ($this->accept && !$file->match($this->accept))
			{
				$error_message = $errors->format("Only the following file types are accepted: %accepted.", [ '%accepted' => implode(', ', $this->accept) ]);
			}

			if ($error_message)
			{
				$errors[File::PATH] = $errors->format('Unable to upload file %file: :message.', [

					'%file' => $file->name,
					':message' => $error_message

				]);
			}
		}
		else if (!$this->key)
		{
			$errors[File::PATH] = $errors->format("File is required.");
		}

		return parent::validate($errors);
	}

	/**
	 * Trigger a {@link File\MoveEvent} when the path of the updated record is updated.
	 */
	protected function process()
	{
		$record = $this->record;
		$oldpath = $record ? $record->path : null;

		$rc = parent::process();

		if ($oldpath)
		{
			$newpath = $this->module->model
			->select('path')
			->filter_by_nid($rc['key'])
			->rc;

			if ($oldpath != $newpath)
			{
				new File\MoveEvent($record, $oldpath, $newpath);
			}
		}

		return $rc;
	}

	protected function resolve_request_file_from_pathname($pathname)
	{
		$filename = basename($pathname);
		$info_pathname = \ICanBoogie\REPOSITORY . 'tmp' . DIRECTORY_SEPARATOR . $filename . '.info';

		if (!file_exists($info_pathname))
		{
			return;
		}

		$properties = json_decode(file_get_contents($info_pathname), true);

		if (!$properties)
		{
			return;
		}

		return \ICanBoogie\HTTP\File::from($properties);
	}
}

namespace Icybee\Modules\Files\File;

/**
 * Event class for the `Icybee\Modules\Files\File` event.
 */
class MoveEvent extends \ICanBoogie\Event
{
	/**
	 * Previous path.
	 *
	 * @var string
	 */
	public $from;

	/**
	 * New path.
	 *
	 * @var string
	 */
	public $to;

	/**
	 * The event is constructed with the type `move`.
	 *
	 * @param \Icybee\Modules\Files\File $target
	 * @param string $from Previous path.
	 * @param string $to New path.
	 */
	public function __construct(\Icybee\Modules\Files\File $target, $from, $to)
	{
		$this->from = $from;
		$this->to = $to;

		parent::__construct($target, 'move');
	}
}
