<?php

namespace Icybee\Modules\Files;

use ICanBoogie\HTTP\Request;

/**
 * A save operation that doesn't require form validation.
 */
class FakeSaveOperation extends Operation\SaveOperation
{
	public function __invoke(Request $request)
	{
		$this->module = $this->app->modules['files'];

		return parent::__invoke($request);
	}

	protected function get_controls()
	{
		return [

			self::CONTROL_FORM => false

		] + parent::get_controls();
	}
}
