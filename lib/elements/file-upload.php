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

use Brickrouge\Element;

class FileUpload extends \Brickrouge\File
{
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
		return '<a class="icon-download-alt" href="' . $path . '" title="' . I18n\t('download', [], [ 'scope' => 'fileupload.element' ]) . '"></a>';
	}
}
