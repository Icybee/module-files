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

use ICanBoogie\HTTP\Request;

use Icybee\Modules\Files\FileModel;
use Icybee\Modules\Files\Module;
use Icybee\Modules\Nodes\Routing\NodesAdminController;

/**
 * @property FileModel $model
 */
class FilesAdminController extends NodesAdminController
{
	protected function is_action_method($action)
	{
		if ($action === 'download')
		{
			return true;
		}

		return parent::is_action_method($action);
	}

	protected function show($id)
	{
		/* @var $record \Icybee\Modules\Files\File */

		$record = $this->model[$id];

		$this->assert_has_permission(Module::PERMISSION_ACCESS, $record);

		return Request::from($this->app->url_for('api:files:show', $record))->send();
	}

	protected function download($id)
	{
		/* @var $record \Icybee\Modules\Files\File */

		$record = $this->model[$id];

		$this->assert_has_permission(Module::PERMISSION_ACCESS, $record);

		return Request::from($this->app->url_for('api:files:download', $record))->send();
	}
}
