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

use function ICanBoogie\create_file;
use ICanBoogie\HTTP\File as HTTPFile;

use Icybee\Modules\Files\Storage\FileStorage;
use Icybee\Modules\Files\Storage\Pathname;

class FileTest extends \PHPUnit_Framework_TestCase
{
	static private $app;

	static public function setUpBeforeClass()
	{
		self::$app = \ICanBoogie\app();
	}

	public function test_get_pathname()
	{
		$uuid = \ICanBoogie\generate_v4_uuid();

		$pathname = $this
			->getMockBuilder(Pathname::class)
			->disableOriginalConstructor()
			->getMock();

		$file_storage = $this
			->getMockBuilder(FileStorage::class)
			->disableOriginalConstructor()
			->setMethods([ 'find' ])
			->getMock();
		$file_storage
			->expects($this->once())
			->method('find')
			->with($uuid)
			->willReturn($pathname);

		$record = $this
			->getMockBuilder(File::class)
			->disableOriginalConstructor()
			->setMethods([ 'get_file_storage' ])
			->getMock();
		$record
			->expects($this->once())
			->method('get_file_storage')
			->willReturn($file_storage);

		/* @var $record File */

		$record->uuid = $uuid;

		$this->assertSame($pathname, $record->pathname);
	}

	public function test_get_file_storage()
	{
		$this->assertSame(self::$app->file_storage, File::from()->file_storage);
	}

	public function test_save_with_http_file()
	{
		$extension = '.jpeg';
		$mime = 'image/jpeg';

		$http_file_origin = create_file();
		$http_file_pathname = $http_file_origin . $extension;

		copy($http_file_origin, $http_file_pathname);

		$record = File::from([

			File::HTTP_FILE => HTTPFile::from([

				'pathname' => $http_file_pathname,
				'type' => $mime

			])

		]);

		$nid = $record->save();

		$this->assertNotEmpty($nid);
		$this->assertObjectNotHasAttribute(File::HTTP_FILE, $record);
		$this->assertSame($extension, $record->extension);
		$this->assertSame($mime, $record->mime);
		$this->assertSame(filesize($http_file_origin), $record->size);

		$pathname = $record->pathname;
		$this->assertNotEmpty($pathname);
		$this->assertFileNotExists($http_file_pathname);
		$this->assertFileEquals($http_file_origin, (string) $pathname);
	}
}
