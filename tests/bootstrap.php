<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$_SERVER['DOCUMENT_ROOT'] = __DIR__;

if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'repository'))
{
	mkdir(__DIR__ . DIRECTORY_SEPARATOR . 'repository');
}

require __DIR__ . '/../vendor/autoload.php';

#
# Create the _core_ instance used for the tests.
#

global $core;

$core = new \ICanBoogie\Core(\ICanBoogie\array_merge_recursive(\ICanBoogie\get_autoconfig(), [

	'config-path' => [

		__DIR__ . DIRECTORY_SEPARATOR . 'config' => 10

	],

	'module-path' => [

		realpath(__DIR__ . '/../')

	]

]));

$core->boot();

$errors = $core->modules->install(new \ICanBoogie\Errors());

if ($errors->count())
{
	foreach ($errors as $module_id => $error)
	{
		echo "$module_id: $error\n";
	}

	exit(1);
}

#
#
#

use Icybee\Modules\Users\User;

$user = User::from([

	'username' => 'admin',
	'email' => 'admin@example.com'

]);

$core->site_id = 0;

$user->save();
