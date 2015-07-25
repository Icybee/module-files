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

class CompatDownloadOperationTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var File
	 */
	static private $record;

	static public function setupBeforeClass()
	{
		self::$record = create_file(__FILE__, [

			'is_online' => true

		]);
	}

	static public function tearDownAfterClass()
	{
		self::$record->delete();
	}

	public function test_success()
	{
		$record = self::$record;
		$request = Request::from("/api/files/{$record->nid}/download");
		$response = $request();

		$this->assertTrue($response->status->is_successful);
		$this->assertEquals(filesize(__FILE__), $response->content_length);
		$this->assertEquals("attachment; filename=" . basename(__FILE__), (string) $response->headers['Content-Disposition']);
	}
}
