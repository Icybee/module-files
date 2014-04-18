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

use Icybee\Modules\Files\GetOperationTest\FakeSaveOperation;

/* @var $response \ICanBoogie\Operation\Response */

class GetOperationTest extends \PHPUnit_Framework_TestCase
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
				Operation::NAME => 'save'

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

	public function test_get()
	{
		$record = self::$record;

		$request = Request::from([

			'uri' => "/api/files/{$record->nid}",
			'method' => 'GET'

		]);

		$response = $request();

		$this->assertTrue($response->is_successful);
		$this->assertInstanceOf('Closure', $response->rc);

		$headers = $response->headers;

		$this->assertEquals('public, max-age=' . GetOperation::CACHE_MAX_AGE, (string) $headers['Cache-Control']);
		$this->assertNotEmpty((string) $headers['Etag']);
		$this->assertNotEmpty((string) $headers['Expires']);
		$this->assertEquals(filesize(__FILE__), (string) $headers['Content-Length']);
		$this->assertEquals('application/x-php', (string) $headers['Content-Type']);
		$this->assertEquals(filemtime(\ICanBoogie\DOCUMENT_ROOT . $record->path), $headers['Last-Modified']->timestamp);

		ob_start();
		$response->rc();
		$body = ob_get_clean();
		$this->assertSame(file_get_contents(__FILE__), $body);

		#
		# Check modified
		#

		$request = Request::from([

			'uri' => "/api/files/{$record->nid}",
			'method' => 'GET',
			'headers' => [

				"Cache-Control" => "max-age=0",
				"If-Modified-Since" => $headers['Last-Modified'],
				"If-None-Match" => $headers['Etag']

			]

		]);

		$response = $request();

		$this->assertEquals(304, $response->status);
		$this->assertTrue($response->rc);
		$this->assertEquals('public, max-age=' . GetOperation::CACHE_MAX_AGE, (string) $response->cache_control);
		$this->assertEquals((string) $headers['Etag'], (string) $response->headers['Etag']);
		$this->assertNotEmpty((string) $headers['Expires']);

		#
		# Get fresh
		#

		$request = Request::from([

			'uri' => "/api/files/{$record->nid}",
			'method' => 'GET',
			'headers' => [

				'Cache-Control' => "no-cache"

			]
		]);

		$response = $request();

		$this->assertEquals(200, $response->status);
		$this->assertInstanceOf('Closure', $response->rc);

		$headers = $response->headers;

		$this->assertEquals('public, max-age=' . GetOperation::CACHE_MAX_AGE, (string) $headers['Cache-Control']);
		$this->assertNotEmpty((string) $headers['Etag']);
		$this->assertNotEmpty((string) $headers['Expires']);
		$this->assertEquals(filesize(__FILE__), (string) $headers['Content-Length']);
		$this->assertEquals('application/x-php', (string) $headers['Content-Type']);
		$this->assertEquals(filemtime(\ICanBoogie\DOCUMENT_ROOT . $record->path), $headers['Last-Modified']->timestamp);
	}
}

namespace Icybee\Modules\Files\GetOperationTest;

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