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

use Icybee\Modules\Files\SaveOperationTest\FakeSaveOperation;

/* @var $response \ICanBoogie\Operation\Response */

class SaveOperationTest extends \PHPUnit_Framework_TestCase
{
	static private $request_basic_properties = [

		'is_post' => true,

		'request_params' => [

			Operation::DESTINATION => 'files',
			Operation::NAME => 'save',

			'siteid' => 0,
			'nativeid' => 0,
			'language' => '',
			'description' => '',
			'MAX_FILE_SIZE' => 16000000

		]

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

		$operation = new FakeSaveOperation;

		try
		{
			$response = $operation($request);

			$this->fail('The Failure exception should have been raised.');
		}
		catch (\ICanBoogie\Operation\Failure $failure)
		{
			$errors = $failure->operation->response->errors;

			$this->assertNotNull($errors[File::PATH]);
			$this->assertStringStartsWith("Unable to upload file", (string) $errors[File::PATH]);
			$this->assertNotEmpty(strpos($errors[File::PATH], $message));
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
		$source_pathname = __FILE__;
		$pathname = DIR . 'tests/sandbox/' . basename(__FILE__);

		copy($source_pathname, $pathname);

		$request = Request::from(self::$request_basic_properties + [

			'files' => [

				SaveOperation::USERFILE => [ 'pathname' => $pathname ]

			]

		]);

		$operation = new FakeSaveOperation;
		$response = $operation($request);

		$this->assertTrue($response->is_successful);
		$this->assertInstanceOf('ICanBoogie\HTTP\File', $operation->file);

		/* @var $record File */

		$record = $operation->record;
		$this->assertInstanceOf('Icybee\Modules\Files\File', $record);
		$this->assertStringStartsWith('/repository/files/bin/', $record->path);
		$this->assertEquals(basename(__FILE__, '.php'), $record->title);
		$this->assertEquals(filesize($source_pathname), $record->size);
		$this->assertEquals('application/x-php', $record->mime);

		# cleanup

		$record->delete();
	}
}

namespace Icybee\Modules\Files\SaveOperationTest;

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