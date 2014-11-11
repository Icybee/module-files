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

/* @var $response \ICanBoogie\Operation\Response */

class DownloadOperationTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var \ICanBoogie\Core;
	 */
	static private $app;

	/**
	 * @var File
	 */
	static private $record;

	static public function setupBeforeClass()
	{
		self::$app = \ICanBoogie\app();
		self::$record = create_file(__FILE__, [

			'is_online' => true

		]);
	}

	static public function tearDownAfterClass()
	{
		self::$app->user->logout();
		self::$record->delete();
	}

	public function test_process()
	{
		$record = self::$record;

		$request = Request::from([

			'uri' => "/api/files/{$record->uuid}/download",
			'method' => 'GET'

		]);

		$response = $request();

		$this->assertEquals("File Transfer", (string) $response->headers['Content-Description']);
		$this->assertEquals("attachment; filename=" . $record->title . $record->extension, (string) $response->headers['Content-Disposition']);
		$this->assertEquals("binary", (string) $response->headers['Content-Transfer-Encoding']);
	}
}
