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
use ICanBoogie\HTTP\Request;
use ICanBoogie\I18n\FormattedString;

/**
 * Downloads a file.
 *
 * The file transfert is handled by PHP, the location of the file is not be revealed.
 *
 * Offline files cannot be downloaded by visitors.
 */
class GetOperation extends \ICanBoogie\Operation
{
	/**
	 * Controls for the operation: record.
	 */
	protected function get_controls()
	{
		return array
		(
			self::CONTROL_RECORD => true
		)

		+ parent::get_controls();
	}

	public function __invoke(Request $request)
	{
		global $core;

		if (!$this->module)
		{
			$this->module = $core->modules['files'];
		}

		$hexnid = $request['hexnid'];

		if ($hexnid)
		{
			$this->key = hexdec($hexnid);
		}

		return parent::__invoke($request);
	}

	/**
	 * Overrides the method to check the availability of the record to the requesting user.
	 *
	 * @throws HTTPException with HTTP code 401, if the user is a guest and the record is
	 * offline.
	 */
	protected function control_record()
	{
		global $core;

		$record = parent::control_record();

		if ($core->user->is_guest && !$record->is_online)
		{
			throw new HTTPError
			(
				\ICanBoogie\format('The requested resource requires authentication: %resource', array
				(
					'%resource' => $record->constructor . '/' . $this->key
				)),

				401
			);
		}

		return $record;
	}

	protected function validate(\ICanboogie\Errors $errors)
	{
		$hash = $this->request['hash'];

		if (!$hash)
		{
			$errors['hash'] = new FormattedString('Hash is required.');
		}
		else if ($this->record && $this->record->hash != $hash)
		{
			$errors['hash'] = new FormattedString('Invalid hash: %hash', array('hash' => $hash));
		}

		return !$errors->count();
	}

	/**
	 * @todo implement Accept-Range
	 */
	protected function process()
	{
		$record = $this->record;
		$response = $this->response;
		$request = $this->request;

		$server_path = $record->server_path;
		$stat = stat($server_path);
		$etag = $record->long_hash;

		$response->cache_control->cacheable = 'public';
		$response->etag = $etag;
		$response->expires = '+1 month';

		if ($request->cache_control->cacheable != 'no-cache')
		{
			$if_none_match = $request->headers['If-None-Match'];
			$if_modified_since = $request->headers['If-Modified-Since'];

			if ($if_modified_since && $if_modified_since->timestamp >= $stat['mtime']
			&& $if_none_match && trim($if_none_match) == $etag)
			{
				$response->status = 304;

				#
				# WARNING: do *not* send any data after that
				#

				return true;
			}
		}

		$response->last_modified = $stat['mtime'];
		$response->content_type = $record->mime;
		$response->content_length = $record->size;

		return function() use ($record)
		{
			$fh = fopen($record->server_path, 'rb');

			if ($fh)
			{
				#
				# Reset time limit for big files
				#

				if (!ini_get('safe_mode'))
				{
					set_time_limit(0);
				}

				while (!feof($fh) && !connection_status())
				{
					echo fread($fh, 1024 * 8);

					#
					# flushing frees memory used by the PHP buffer
					#

					flush();
				}

				fclose($fh);
			}
		};
	}
}