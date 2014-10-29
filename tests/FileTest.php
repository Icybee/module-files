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

class FileTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @dataProvider provide_test_get_extension
	 *
	 * @param string $expected Expected extension.
	 * @param array $properties Properties used to create the {@link File} record.
	 */
	public function test_get_extension($expected, array $properties)
	{
		$record = File::from($properties);

		$this->assertEquals($expected, $record->extension);
	}

	public function provide_test_get_extension()
	{
		return [

			[ '.png',      [ 'path' => '/path/to/image.png' ] ],
			[ '.document', [ 'path' => '/path/to/image.document' ] ],
			[ '.gz',       [ 'path' => '/path/to/image.tar.gz' ] ],
			[ '',          [ 'path' => '/path/to/image' ] ]

		];
	}

	public function test_save()
	{
		$title = uniqid(null, true);
		$filename = $title . ".php";
		$pathname = \ICanBoogie\REPOSITORY . "tmp/{$filename}";

		copy(__FILE__, $pathname);
		$size = filesize($pathname);

		$record = File::from([

			File::HTTP_FILE => \ICanBoogie\HTTP\File::from([ 'pathname' => $pathname ])

		]);

		$nid = $record->save();

		$this->assertEquals($nid, $record->nid);
		$this->assertEquals("/repository/files/bin/{$nid}-" . \ICanBoogie\normalize($title) . ".php", $record->path);
		$this->assertEquals("application/x-php", $record->mime);
		$this->assertEquals($size, $record->size);
		$this->assertEquals($title, $record->title);
		$this->assertFileExists(dirname(\ICanBoogie\REPOSITORY) . $record->path);
		$this->assertObjectNotHasAttribute(File::HTTP_FILE, $record);

		$record->title = "Madonna";
		$record->save();

		$this->assertEquals("/repository/files/bin/{$nid}-madonna.php", $record->path);
		$this->assertFileExists(dirname(\ICanBoogie\REPOSITORY) . $record->path);

		$record->delete();
		$this->assertFileNotExists(dirname(\ICanBoogie\REPOSITORY) . $record->path);
	}
}
