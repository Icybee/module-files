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

use ICanBoogie\I18n;
use ICanBoogie\Uploaded;

use Brickrouge\Element;
use Brickrouge\A;

class FileUpload extends \Brickrouge\File
{
	protected $record;

	/**
	 * If the `value` attribute is set to a {@link File} record, its value is set to
	 * {@link $record} then it is changed to the image server path.
	 */
	public function offsetSet($attribute, $value)
	{
		if ($attribute == 'value')
		{
			if ($value instanceof File)
			{
				$this->record = $value;

				$value = $value->server_path;
			}
			else
			{
				$this->record = null;
			}
		}

		parent::offsetSet($attribute, $value);
	}

	/**
	 * If the value was a {@link File} record, the value is changed to the hash of the record's
	 * file.
	 */
	protected function render_reminder($name, $value)
	{
		if ($this->record)
		{
			$value = $this->record->hash;
		}

		return parent::render_reminder($name, $value);
	}

	protected function infos()
	{
		$path = $this['value'];
		$details = $this->details($path);
		$preview = $this->preview($path);

		$rc = '';

		if ($preview)
		{
			$rc .= '<div class="preview">';
			$rc .= $preview;
			$rc .= '</div>';
		}

		if ($details)
		{
			$rc .= '<ul class="details">';

			foreach ($details as $detail)
			{
				$rc .= '<li>' . $detail . '</li>';
			}

			$rc .= '</ul>';
		}

		return $rc;
	}

	protected function preview($path)
	{
		return new A
		(
			'', \ICanBoogie\strip_root($path), array
			(
				'class' => 'icon-download-alt',
				'title' => I18n\t('download', array(), array('scope' => 'fileupload.element'))
			)
		);
	}

	protected function alter_dataset(array $dataset)
	{
		$limit = $this[self::FILE_WITH_LIMIT] ?: 2 * 1024;

		if ($limit === true)
		{
			$limit = ini_get('upload_max_filesize') * 1024;
		}

		return array
		(
			'name' => $this['name'],
			'max-file-size' => $limit * 1024
		)

		+ parent::alter_dataset($dataset);
	}
}