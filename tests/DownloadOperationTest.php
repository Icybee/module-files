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

use ICanBoogie\HTTP\Request;
use ICanBoogie\Operation;

use Icybee\Modules\Files\DownloadOperationTest\FakeSaveOperation;

/* @var $response \ICanBoogie\Operation\Response */

class DownloadOperationTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var File
	 */
	static private $record;

	static public function setupBeforeClass()
	{
		global $core;

		$core->models['users'][1]->login();

		$pathname = \ICanBoogie\REPOSITORY . 'tmp' . DIRECTORY_SEPARATOR. basename(__FILE__);

		copy(__FILE__, $pathname);

		$request = Request::from([

			'is_post' => true,

			'request_params' => [

				Operation::DESTINATION => 'files',
				Operation::NAME => 'save',

				'siteid' => 0,
				'nativeid' => 0,
				'language' => '',
				'description' => ''

			],

			'files' => [

				SaveOperation::USERFILE => [ 'pathname' => $pathname ]

			]

		]);

		$operation = new FakeSaveOperation;
		$response = $operation($request);

		self::$record = $operation->record;
	}

	static public function tearDownAfterClass()
	{
		global $core;

		$core->user->logout();

		self::$record->delete();
	}

	public function test_process()
	{
		$record = self::$record;

		$request = Request::from([

			'uri' => "/api/files/{$record->nid}/download",
			'method' => 'GET'

		]);

		$response = $request();

		$this->assertEquals("File Transfer", (string) $response->headers['Content-Description']);
		$this->assertEquals("attachment; filename=" . $record->title . $record->extension, (string) $response->headers['Content-Disposition']);
		$this->assertEquals("binary", (string) $response->headers['Content-Transfer-Encoding']);
	}
}

namespace Icybee\Modules\Files\DownloadOperationTest;

use ICanBoogie\HTTP\Request;

class FakeSaveOperation extends \Icybee\Modules\Files\SaveOperation
{
	public function __invoke(Request $request)
	{
		global $core;

		$this->module = $core->modules['files'];

		return parent::__invoke($request);
	}

	protected function get_controls()
	{
		return [

			self::CONTROL_FORM => false

		] + parent::get_controls();
	}
}