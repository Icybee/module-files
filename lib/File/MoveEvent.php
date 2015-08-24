<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Files\File;

use ICanBoogie\Event;

use Icybee\Modules\Files\File;

/**
 * Event class for the `Icybee\Modules\Files\File::move` event.
 */
class MoveEvent extends Event
{
	/**
	 * Previous path.
	 *
	 * @var string
	 */
	public $from;

	/**
	 * New path.
	 *
	 * @var string
	 */
	public $to;

	/**
	 * The event is constructed with the type `move`.
	 *
	 * @param File $target
	 * @param string $from Previous path.
	 * @param string $to New path.
	 */
	public function __construct(File $target, $from, $to)
	{
		$this->from = $from;
		$this->to = $to;

		parent::__construct($target, 'move');
	}
}
