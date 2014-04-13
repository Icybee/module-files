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

			[ '.png', [ 'path' => '/path/to/image.png' ] ],
			[ '.document', [ 'path' => '/path/to/image.document' ] ],
			[ '.gz', [ 'path' => '/path/to/image.tar.gz' ] ],
			[ '', [ 'path' => '/path/to/image' ] ]

		];
	}
}