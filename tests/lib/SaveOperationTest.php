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

use function ICanBoogie\get_sandbox_directory;
use ICanBoogie\HTTP\File as HTTPFile;
use ICanBoogie\HTTP\Request;
use ICanBoogie\Operation;

use Icybee\Modules\Files\Operation\SaveOperation;
use Icybee\Modules\Users\User;

/* @var $response \ICanBoogie\Operation\Response */

class SaveOperationTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var \ICanBoogie\Core|\Icybee\Binding\CoreBindings
	 */
	static private $app;

	static private $request_basic_properties = [

		'is_post' => true,

		'request_params' => [

			Operation::DESTINATION => 'files',
			Operation::NAME => 'save',

			'MAX_FILE_SIZE' => 16000000

		]

	];

	static public function setupBeforeClass()
	{
		/* @var $user User */

		self::$app = \ICanBoogie\app();

		$user = self::$app->models['users'][1];
		$user->login();
	}

	static public function tearDownAfterClass()
	{
		self::$app->user->logout();
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

			$this->assertNotNull($errors[File::HTTP_FILE]);
			$error = (string) $errors[File::HTTP_FILE][0];
			$this->assertStringStartsWith("Unable to upload file", $error);
			$this->assertNotEmpty(strpos($error, $message));
		}
	}

	public function provide_test_upload_error()
	{
		$size = (int) ini_get('upload_max_filesize') * 1024 * 1024 * 3;

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

	public function test_empty()
	{
		$request = Request::from(self::$request_basic_properties);
		$operation = new FakeSaveOperation;

		try
		{
			$response = $operation($request);

			$this->fail("The Failure exception should have been raise.");
		}
		catch (\ICanBoogie\Operation\Failure $e)
		{
			$errors = $e->operation->response->errors;

			$this->assertNotNull($errors[File::HTTP_FILE]);
		}
	}

	public function test_successful()
	{
		$extension = '.php';
		$pathname = get_sandbox_directory() . '/' . \ICanBoogie\generate_v4_uuid() . $extension;
		copy(__FILE__, $pathname);
		$size = filesize($pathname);

		$request = Request::from(self::$request_basic_properties + [

			'files' => [

				File::HTTP_FILE => [ 'pathname' => $pathname ]

			]

		]);

		$operation = new FakeSaveOperation;
		$response = $operation($request);

		$this->assertTrue($response->status->is_successful);
		$this->assertInstanceOf(HTTPFile::class, $operation->file);
		$this->assertFileNotExists($pathname);

		/* @var $record File */

		$record = $operation->record;
		$this->assertInstanceOf(File::class, $record);
		$this->assertEquals(basename($pathname, $extension), $record->title);
		$this->assertEquals($size, $record->size);
		$this->assertEquals('application/x-php', $record->mime);

		# save again

		$path = $record->pathname;

		$request = Request::from(\ICanBoogie\array_merge_recursive(self::$request_basic_properties, [

			'request_params' => $record->to_array() + [

				Operation::KEY => $record->nid

			]

		]));

		$operation = new FakeSaveOperation;
		$response = $operation($request);

		$this->assertTrue($response->status->is_successful);
		$this->assertEquals($path, $record->pathname);

		# cleanup

		$pathname = $record->pathname;
		$record->delete();
		$this->assertFileNotExists((string) $pathname);
	}
}
