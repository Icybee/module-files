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

class Hooks
{
	/**
	 * Removes unused files after a record has been deleted.
	 *
	 * @param \ICanBoogie\Operation\ProcessEvent $event
	 * @param DeleteOperation $target
	 */
	static public function on_delete(\ICanBoogie\Operation\ProcessEvent $event, DeleteOperation $target)
	{
		Module::removed_unused_files();
	}

	static public function before_save(\ICanBoogie\Operation\BeforeProcessEvent $event, SaveOperation $target)
	{
		global $core;

		if (!$target->key)
		{
			return;
		}

		$previous_path = $target->record->path;

		$eh = $core->events->attach(get_class($target) . '::process', function(\ICanBoogie\Operation\ProcessEvent $event, SaveOperation $target_again) use(&$eh, $target, $previous_path) {

			if ($target != $target_again)
			{
				return;
			}

			$eh->detach();

			$record = $target->record;
			$path = $record->path;

			if ($previous_path == $path)
			{
				return;
			}

			\ICanBoogie\log("move file from '$previous_path' to '$path'");

			new File\MoveEvent($record, $previous_path, $path);
		});
	}

	/**
	 * Removes unused files after a file has been saved.
	 *
	 * @param \ICanBoogie\Operation\ProcessEvent $event
	 * @param SaveOperation $target
	 */
	static public function on_save(\ICanBoogie\Operation\ProcessEvent $event, SaveOperation $target)
	{
		Module::removed_unused_files();
	}
}