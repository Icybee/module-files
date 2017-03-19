<?php

namespace Icybee\Modules\Files\UploadOperationTest;

use ICanBoogie\HTTP\Request;

/**
 * @property \ICanBoogie\Application $app
 */
class FakeUploadOperation extends \Icybee\Modules\Files\Operation\UploadOperation
{
	/**
	 * @inheritdoc
	 */
	public function action(Request $request)
	{
		$this->module = $this->app->modules['files'];

		return parent::action($request);
	}
}
