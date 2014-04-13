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

use Icybee\Modules\Files\UploadOperationTest\FakeUploadOperation;

/* @var $response \ICanBoogie\Operation\Response */

class UploadOperationTest extends \PHPUnit_Framework_TestCase
{
	static private $request_basic_properties = [

		'is_post' => true,
		'uri' => '/api/files/upload'

	];

	static public function setupBeforeClass()
	{
		global $core;

		$core->models['users'][1]->login();
	}

	static public function tearDownAfterClass()
	{
		global $core;

		$core->user->logout();
	}

	/**
	 * @dataProvider provide_test_upload_error
	 */
	public function test_upload_error($message, $properties)
	{
		$request = Request::from(self::$request_basic_properties + [

			'files' => [

				SaveOperation::USERFILE => [ 'name' => 'example.zip' ] + $properties

			]

		]);

		$operation = new FakeUploadOperation;

		try
		{
			$response = $operation($request);

			$this->fail('The Failure exception should have been raised.');
		}
		catch (\ICanBoogie\Operation\Failure $failure)
		{
			$errors = $failure->operation->response->errors;

			$this->assertNotNull($errors[SaveOperation::USERFILE]);
			$this->assertStringStartsWith($message, (string) $errors[SaveOperation::USERFILE]);
		}
	}

	public function provide_test_upload_error()
	{
		$size = ini_get('upload_max_filesize') * 1024 * 1024 * 3;

		return [

			[ "Maximum file size is",                    [ 'error' => UPLOAD_ERR_INI_SIZE,  'size' => $size ] ],
			[ "Maximum file size is",                    [ 'error' => UPLOAD_ERR_FORM_SIZE, 'size' => $size ] ],
			[ "The uploaded file was only",              [ 'error' => UPLOAD_ERR_PARTIAL ] ],
			[ "No file was uploaded",                    [ 'error' => UPLOAD_ERR_NO_FILE ] ],
			[ "Missing a temporary folder",              [ 'error' => UPLOAD_ERR_NO_TMP_DIR ] ],
			[ "Failed to write file to disk",            [ 'error' => UPLOAD_ERR_CANT_WRITE ] ],
			[ "A PHP extension stopped the file upload", [ 'error' => UPLOAD_ERR_EXTENSION ] ]

		];
	}

	public function test_successful()
	{
		$source = __FILE__;
		$pathname = DIR . 'tests/sandbox/' . basename(__FILE__);

		copy($source, $pathname);

		$request = Request::from(self::$request_basic_properties + [

			'files' => [

				SaveOperation::USERFILE => [ 'pathname' => $pathname ]

			]

		]);

		$operation = new FakeUploadOperation;
		$response = $operation($request);

		$this->assertTrue($response->is_successful);
		$this->assertInstanceOf('ICanBoogie\HTTP\File', $operation->file);

		$rc = $response->rc;
		$this->assertArrayHasKey('title', $rc);
		$this->assertArrayHasKey('extension', $rc);
		$this->assertArrayHasKey('size', $rc);
		$this->assertArrayHasKey('type', $rc);
		$this->assertArrayHasKey('pathname', $rc);

		$this->assertEquals(basename($source, '.php'), $rc['title']);
		$this->assertEquals('.php', $rc['extension']);
		$this->assertEquals(filesize($source), $rc['size']);
		$this->assertEquals('application/x-php', $rc['type']);
		$this->assertStringStartsWith('/repository/tmp/', $rc['pathname']);
	}
}

namespace Icybee\Modules\Files\UploadOperationTest;

use ICanBoogie\HTTP\Request;

class FakeUploadOperation extends \Icybee\Modules\Files\UploadOperation
{
	public function __invoke(Request $request)
	{
		global $core;

		$this->module = $core->modules['files'];

		return parent::__invoke($request);
	}
}