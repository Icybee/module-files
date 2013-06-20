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

use ICanBoogie\I18n\FormattedString;
use ICanBoogie\HTTP\Request;
use ICanBoogie\Uploaded;
use ICanBoogie\HTTP\Response;

/**
 * Saves a file.
 *
 * @property-read array $accepted_mime The accepted MIME types.
 */
class SaveOperation extends \Icybee\Modules\Nodes\SaveOperation
{
	/**
	 * Hash of the updated file.
	 *
	 * @var string
	 */
	protected $file_hash;

	/**
	 * MIME type of the uploaded file.
	 *
	 * @var string
	 */
	protected $file_mime;

	/**
	 * Absolute path to the updated file.
	 *
	 * @var string
	 */
	protected $file_path;

	/**
	 * Path of the temporary file.
	 *
	 * @var string
	 */
	protected $tmp_file_path;

	/**
	 * Returns the accepted MIME types.
	 *
	 * @return array
	 */
	protected function volatile_get_accepted_mime()
	{
		return array();
	}

	/**
	 * Sets {@link File::HASH} to the {@link $file_hash} property, or unset it if the property is
	 * empty. Sets {@link File::SIZE} and {@link File::MIME} according to the {@link $file_path}
	 * property, or unset them if the property is empty.
	 */
	protected function get_properties()
	{
		$properties = parent::get_properties();

		$hash = $this->file_hash;

		if ($hash)
		{
			$properties[File::HASH] = $hash;
		}
		else
		{
			unset($properties[File::HASH]);
		}

		$path = $this->file_path;

		if ($path)
		{
			$properties[File::SIZE] = filesize($path);
		}
		else
		{
			unset($properties[File::SIZE]);
		}

		$mime = $this->file_mime;

		if ($mime)
		{
			$properties[File::MIME] = $mime;
		}
		else
		{
			unset($properties[File::MIME]);
		}

		return $properties;
	}

	/**
	 * The repository is cleaned before the method is passed to the parent class.
	 */
	public function __invoke(Request $request)
	{
		$this->module->clean_repository();

		try
		{
			$response = parent::__invoke($request);
		}
		catch (\Exception $e)
		{
			#
			# Because the operation failed, the file that was created is removed.
			#

			$file_path = $this->file_path;

			if ($file_path && file_exists($file_path))
			{
				unlink($file_path);
			}

			throw $e;
		}

		#
		# If the response is successful the temporary file can be removed.
		#

		$tmp_file_name = $this->tmp_file_path;

		if ($tmp_file_name && $response instanceof Response && $response->is_ok)
		{
			unlink($tmp_file_name);
		}

		#
		# If the response is not successful the managed file is removed.
		#

		$file_path = $this->file_path;

		if ($file_path && (!($response instanceof Response) || !$response->is_ok))
		{
			unlink($file_path);
		}

		return $response;
	}

	/**
	 * Invokes the {@link control_file} method before anything, to check if a file was uploaded.
	 */
	protected function control(array $controls)
	{
		if (!$this->control_file())
		{
			return;
		}

		return parent::control($controls);
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
	protected function control_file()
	{
		$file_hash = null;
		$file_mime = null;
		$file_path = null;

		$request = $this->request;
		$tmp_filename = $request[File::PATH];

		if ($tmp_filename)
		{
			#
			# PATH equals HASH when saving a record with an unchanged file.
			#

			if ($this->record && $this->record->hash == $tmp_filename)
			{
				return true;
			}

			$tmp_file_path = \ICanBoogie\REPOSITORY . 'tmp' . DIRECTORY_SEPARATOR . basename($tmp_filename);
			$this->tmp_file_path = $tmp_file_path;

			if (!file_exists($tmp_file_path))
			{
				$this->response->errors[File::PATH] = new FormattedString("Missing temporary file: %name.", array('name' => $tmp_filename));

				return false;
			}

			$file_hash = File::create_hash($tmp_file_path);
			$file_extension = pathinfo($tmp_file_path, PATHINFO_EXTENSION);
			$file_name = $file_hash . ($file_extension ? '.' . $file_extension : '');
			$file_path = \ICanBoogie\REPOSITORY . 'files' . DIRECTORY_SEPARATOR . $file_name;
			$file_mime = Uploaded::getMIME($file_path);

			if (!file_exists($file_path))
			{
				copy($tmp_file_path, $file_path);
			}
		}
		else // file upload
		{
			$file = new Uploaded(File::PATH, $this->accepted_mime);

			if ($file->location)
			{
				$file_hash = File::create_hash($file->location);
				$file_extension = $file->extension;
				$file_name = $file_hash . $file_extension;
				$file_path = \ICanBoogie\REPOSITORY . 'files' . DIRECTORY_SEPARATOR . $file_name;
				$file_mime = $file->mime;

				if (!file_exists($file_path))
				{
					$file->move($file_path);
				}

				if (!$request[File::TITLE])
				{
					$request[File::TITLE] = $file->name;
				}
			}
			else
			{
				$this->errors[File::PATH] = new FormattedString('Unable to upload file %file: :message.', array('%file' => $file->name, ':message' => $file->er_message));

				return false;
			}
		}

		$this->file_hash = $file_hash;
		$this->file_mime = $file_mime;
		$this->file_path = $file_path;

		if ($this->key)
		{
			return !!$file_hash;
		}

		return true;
	}

	/**
	 * Trigger a {@link File\MoveEvent} when the path of the updated record is modified.
	 */
	protected function process()
	{
		return parent::process() + array
		(
			'hash' => $this->record->hash,
			'path' => $this->record->url('get')
		);
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
		parent::__construct($target, 'move', array('from' => $from, 'to' => $to));
	}
}