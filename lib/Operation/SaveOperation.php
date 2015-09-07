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

use ICanBoogie\Errors;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\File as HTTPFile;

use Icybee\Modules\Files\File;
use Icybee\Modules\Files\Module;

/**
 * Save a file.
 *
 * @property-read HTTPFile|null $file The file associated with the request.
 * @property Module $module
 * @property File $record
 */
class SaveOperation extends \Icybee\Modules\Nodes\Operation\SaveOperation
{
	/**
	 * Name of the _user-file_ slot.
	 *
	 * @var string
	 */
	const USERFILE = 'path';

	/**
	 * @var HTTPFile|bool The optional file to save with the record.
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
	 * Unset {@link File::MIME}, {@link File::SIZE}, and {@link File::EXTENSION} properties because
	 * they can only be set from a HTTP file. {@link File::DESCRIPTION} is set to and empty
	 * string if it is not defined.
	 *
	 * {@inheritdoc}
	 */
	protected function lazy_get_properties()
	{
		$properties = parent::lazy_get_properties() + [

			'description' => ''

		];

		unset($properties[File::MIME]);
		unset($properties[File::SIZE]);
		unset($properties[File::EXTENSION]);

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
	 *
	 * @inheritdoc
	 */
	protected function control(array $controls)
	{
		$request = $this->request;
		$path = $request[File::PATH];
		$file = $request->files[self::USERFILE];

		if ($file && $file->is_valid)
		{
			$filename = \ICanBoogie\generate_v4_uuid();
			$pathname = \ICanBoogie\REPOSITORY . 'tmp' . DIRECTORY_SEPARATOR . $filename;

			$file->move($pathname);
		}
		else if ($path && strpos($path, \ICanBoogie\strip_root(\ICanBoogie\REPOSITORY . "files")) !== 0)
		{
			$file = $this->resolve_request_file_from_pathname($path);

			if (!$file)
			{
				$this->response->errors[File::PATH]->add("Invalid or deleted file: %pathname", [ 'pathname' => $path ]);
			}
		}

		unset($request[File::PATH]);

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
	 *
	 * @inheritdoc
	 */
	protected function validate(Errors $errors)
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
				$errors->add(File::PATH, "Unable to upload file %file: :message.", [

					'%file' => $file->name,
					':message' => $error_message

				]);
			}
		}
		else if (!$this->key)
		{
			$errors[File::PATH]->add("File is required.");
		}

		return parent::validate($errors);
	}

	/**
	 * Trigger a {@link File\MoveEvent} when the path of the updated record is updated.
	 *
	 * @inheritdoc
	 */
	protected function process()
	{
		$record = $this->record;
		$oldpath = $record ? $record->path : null;

		$rc = parent::process();

		if ($oldpath)
		{
			$newpath = $this->record->path;

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
			return null;
		}

		$properties = json_decode(file_get_contents($info_pathname), true);

		if (!$properties)
		{
			return null;
		}

		return HTTPFile::from($properties);
	}
}
