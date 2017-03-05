<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Files\Routing;

use ICanBoogie\HTTP\FileResponse;
use ICanBoogie\Routing\Controller;
use ICanBoogie\Binding\Routing\ControllerBindings;
use ICanBoogie\Binding\Routing\ForwardUndefinedPropertiesToApplication;
use ICanBoogie\Module\ControllerBindings as ModuleBindings;

use Icybee\Modules\Files\Binding\ApplicationBindings;
use Icybee\Modules\Files\File;

/**
 * Files public controller.
 */
class FilesController extends Controller
{
	use Controller\ActionTrait;
	use ControllerBindings, ApplicationBindings, ModuleBindings;
	use ForwardUndefinedPropertiesToApplication;

	/**
	 * @param string $uuid
	 *
	 * @return FileResponse
	 */
	public function action_get_show($uuid)
	{
		$pathname = $this->file_storage->find($uuid);

		if (!$pathname)
		{
			return null;
		}

		return new FileResponse($pathname, $this->request, [

			FileResponse::OPTION_ETAG => $pathname->hash

		]);
	}

	/**
	 * @param string $uuid
	 *
	 * @return FileResponse
	 */
	public function action_get_download($uuid)
	{
		$matches = $this->file_storage_index->find($uuid);

		if (!$matches)
		{
			return null;
		}

		$key = $matches[0];
		$pathname = $this->file_storage->find($key);

		if (!$pathname)
		{
			return null;
		}

		/* @var $record File */

		$record = $this->model[$key->id];

		return new FileResponse($pathname, $this->request, [

			FileResponse::OPTION_ETAG => $pathname->hash,
			FileResponse::OPTION_FILENAME => $record->title . $record->extension,
			FileResponse::OPTION_MIME => $record->mime

		]);
	}
}
