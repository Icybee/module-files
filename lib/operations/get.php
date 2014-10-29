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

use ICanBoogie\HTTP\HTTPError;

/**
 * Get a file.
 *
 * The file transfer is handled by PHP, the location of the file is not be revealed.
 *
 * Offline files cannot be obtained by visitors.
 *
 * @property-read $record File
 */
class GetOperation extends \ICanBoogie\Operation
{
	const CACHE_MAX_AGE = 2592000; // One month

	/**
	 * Controls for the operation: record.
	 */
	protected function get_controls()
	{
		return [

			self::CONTROL_RECORD => true

		] + parent::get_controls();
	}

	protected function lazy_get_record()
	{
		$uuid = $this->request['uuid'];

		if (!$uuid)
		{
			return;
		}

		$nid = $this->module->model->select('nid')->filter_by_uuid($uuid)->rc;

		if (!$nid)
		{
			return;
		}

		return $this->module->model[$nid];
	}

	/**
	 * Overrides the method to check the availability of the record to the requesting user.
	 *
	 * @throws HTTPException with HTTP code 401, if the user is a guest and the record is
	 * offline.
	 */
	protected function control_record()
	{
		$record = parent::control_record();

		if ($this->app->user->is_guest && !$record->is_online)
		{
			throw new HTTPError
			(
				\ICanBoogie\format('The requested resource requires authentication: %resource', [

					'%resource' => $record->constructor . '/' . $this->key

				]), 401
			);
		}

		return $record;
	}

	protected function validate(\ICanboogie\Errors $errors)
	{
		return true;
	}

	protected function process()
	{
		/* @var $record File */
		$record = $this->record;
		$pathname = \ICanBoogie\DOCUMENT_ROOT . $record->path;
		$hash = sha1_file($pathname);
		$modified_time = filemtime($pathname);

		$response = $this->response;
		$response->cache_control->cacheable = 'public';
		$response->cache_control->max_age = self::CACHE_MAX_AGE;
		$response->etag = $hash;
		$response->expires = '+1 month';

		$request = $this->request;

		if ($request->cache_control->cacheable != 'no-cache')
		{
			$if_none_match = $request->headers['If-None-Match'];
			$if_modified_since = $request->headers['If-Modified-Since'];

			if (!$if_modified_since->is_empty
			&& $if_modified_since->timestamp >= $modified_time
			&& $if_none_match == $hash)
			{
				$response->status = 304;

				#
				# WARNING: do *not* send any data after that
				#

				return true;
			}
		}

		$response->content_type = $record->mime;
		$response->content_length = $record->size;
		$response->last_modified = $modified_time;

		$fh = fopen($pathname, 'rb');

		if (!$fh)
		{
			throw new HTTPError("Unable to lock file.");
		}

		return function() use ($fh)
		{
			#
			# Reset time limit for big files
			#

			if (!ini_get('safe_mode'))
			{
				set_time_limit(0);
			}

			while (!feof($fh) && !connection_aborted())
			{
				echo fread($fh, 1024 * 8);

				#
				# flushing frees memory used by the PHP buffer
				#

				flush();
			}

			fclose($fh);
		};
	}
}
