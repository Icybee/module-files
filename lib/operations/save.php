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
	 * Unset the `mime` and `size` properties because they cannot be updated by the user. If the
	 * `file` property is defined, which is the case when an asynchronous upload happend, it is
	 * copied to the `path` property.
	 */
	protected function lazy_get_properties()
	{
		$properties = parent::lazy_get_properties();

		unset($properties[File::MIME]);
		unset($properties[File::SIZE]);

		$file = $this->file;

		if ($file && $file->is_valid)
		{
			$properties[File::MIME] = $file->type;
			$properties[File::SIZE] = $file->size;
		}

		#
		# File:PATH is set to true when the file is not mandatory and there is no uploaded file in
		# order for the form still validates, in which case the property must be unset, otherwise
		# the boolean is used a the new path.
		#

		if (isset($properties[File::PATH]) && $properties[File::PATH] === true)
		{
			unset($properties[File::PATH]);
		}

		return $properties;
	}

	/**
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
	 * TODO: maybe this is not ideal, since the file upload should be made optionnal when the form
	 * is generated to edit existing entries.
	 */
	protected function control(array $controls)
	{
		global $core;

		$request = $this->request;

		/* @var $file \ICanBoogie\HTTP\File */

		$this->file = $file = $request->files[self::USERFILE];

		if ($file && $file->is_valid)
		{
			$filename = uniqid(null, true) . $file->extension;
			$pathname = \ICanBoogie\REPOSITORY . 'tmp' . DIRECTORY_SEPARATOR . $filename;

			$file->move($pathname);

			$request[File::PATH] = \ICanBoogie\strip_root($pathname);

			if (!$request[File::TITLE])
			{
				$request[File::TITLE] = $file->unsuffixed_name;
			}
		}
		else if ($file && $this->key)
		{
			unset($request[File::PATH]);
		}

		return parent::control($controls);
	}

	/**
	 * The method validates unless there was an error during the file upload.
	 */
	protected function validate(\ICanboogie\Errors $errors)
	{
		global $core;

		$file = $this->file;

		if ($file)
		{
			$error_message = $file->error_message;

			$max_file_size = $core->registry["{$this->module->flat_id}.max_file_size"];

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