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

// use Icybee\Modules\Files\GetOperationTest\FakeSaveOperation;

/* @var $response \ICanBoogie\Operation\Response */

class GetOperationTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var \ICanBoogie\Core
	 */
	static private $app;

	/**
	 * @var \Icybee\Modules\Users\User
	 */
	static private $user;

	/**
	 * @var File
	 */
	static private $record;

	static public function setupBeforeClass()
	{
		self::$app = $app = \ICanBoogie\app();
		self::$record = create_file(__FILE__, [

			'is_online' => true

		]);

		self::$user = self::$record->user;
	}

	static public function tearDownAfterClass()
	{
		self::$record->delete();
	}

	public function test_get()
	{
		$record = self::$record;

		$request = Request::from([

			'uri' => "/api/files/{$record->uuid}",
			'method' => 'GET'

		]);

		$response = $request();

		$this->assertTrue($response->status->is_successful);
		$this->assertInstanceOf('Closure', $response->rc);

		$headers = $response->headers;

		$this->assertEquals('public, max-age=' . ShowOperation::CACHE_MAX_AGE, (string) $headers['Cache-Control']);
		$this->assertNotEmpty((string) $headers['Etag']);
		$this->assertNotEmpty((string) $headers['Expires']);
		$this->assertEquals(filesize(__FILE__), (string) $headers['Content-Length']);
		$this->assertEquals('application/x-php', (string) $headers['Content-Type']);
		$this->assertEquals(filemtime(\ICanBoogie\DOCUMENT_ROOT . $record->path), $headers['Last-Modified']->timestamp);

		ob_start();

		$rc = $response->rc;

		$this->assertInstanceOf(\Closure::class, $rc);
		$rc();
		$body = ob_get_clean();
		$this->assertSame(file_get_contents(__FILE__), $body);

		#
		# Check modified
		#

		$request = Request::from([

			'uri' => "/api/files/{$record->uuid}",
			'method' => 'GET',
			'headers' => [

				"Cache-Control" => "max-age=0",
				"If-Modified-Since" => $headers['Last-Modified'],
				"If-None-Match" => $headers['Etag']

			]

		]);

		$response = $request();

		$this->assertEquals(304, $response->status->code);
		$this->assertTrue($response->rc);
		$this->assertEquals('public, max-age=' . ShowOperation::CACHE_MAX_AGE, (string) $response->cache_control);
		$this->assertEquals((string) $headers['Etag'], (string) $response->headers['Etag']);
		$this->assertNotEmpty((string) $headers['Expires']);

		#
		# Get fresh
		#

		$request = Request::from([

			'uri' => "/api/files/{$record->uuid}",
			'method' => 'GET',
			'headers' => [

				'Cache-Control' => "no-cache"

			]
		]);

		$response = $request();

		$this->assertEquals(200, $response->status->code);
		$this->assertInstanceOf('Closure', $response->rc);

		$headers = $response->headers;

		$this->assertEquals('public, max-age=' . ShowOperation::CACHE_MAX_AGE, (string) $headers['Cache-Control']);
		$this->assertNotEmpty((string) $headers['Etag']);
		$this->assertNotEmpty((string) $headers['Expires']);
		$this->assertEquals(filesize(__FILE__), (string) $headers['Content-Length']);
		$this->assertEquals('application/x-php', (string) $headers['Content-Type']);
		$this->assertEquals(filemtime(\ICanBoogie\DOCUMENT_ROOT . $record->path), $headers['Last-Modified']->timestamp);
	}

	/**
	 * @expectedException \Exception
	 */
	public function test_get_offline()
	{
		$record = self::$record;
		$record->is_online = false;
		$record->save();

		$request = Request::from("/api/files/{$record->uuid}");
		$response = $request();
	}
}
