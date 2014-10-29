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
 * Downloads a file.
 *
 * The file transfer is handled by PHP, the location of the file is not be revealed.
 *
 * Offline files cannot be downloaded by visitors.
 */
class DownloadOperation extends GetOperation
{
	protected function process()
	{
		$rc = parent::process();

		if ($rc instanceof \Closure)
		{
			$record = $this->record;
			$response = $this->response;

			$response->headers['Content-Description'] = 'File Transfer';
			$response->headers['Content-Disposition']->type = 'attachment';
			$response->headers['Content-Disposition']->filename = $record->title . $record->extension;
			$response->headers['Content-Transfer-Encoding'] = 'binary';
		}

		return $rc;
	}
}
