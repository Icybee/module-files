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
use ICanBoogie\HTTP\Request;

use Icybee\Modules\Files\Binding\CoreBindings;
use Icybee\Modules\Files\File;
use Icybee\Modules\Files\FileModel;
use Icybee\Modules\Files\Module;
use Icybee\Modules\Nodes\Routing\NodesAdminController;

/**
 * @property FileModel $model
 */
class FilesAdminController extends NodesAdminController
{
	use CoreBindings;

	protected function action_show($id)
	{
		/* @var $record File */

		$record = $this->model[$id];

		$this->assert_has_permission(Module::PERMISSION_ACCESS, $record);

		$pathname = $this->file_storage->find($id);

		if (!$pathname)
		{
			return null;
		}

		return new FileResponse($pathname, $this->request, [

			FileResponse::OPTION_ETAG => $pathname->hash

		]);
	}

	protected function action_download($id)
	{
		/* @var $record File */

		$record = $this->model[$id];

		$this->assert_has_permission(Module::PERMISSION_ACCESS, $record);

		$matches = $this->file_storage_index->find($id);

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
