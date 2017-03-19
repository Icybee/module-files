<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie;

use ICanBoogie\HTTP\Request;
use Icybee\Modules\Files\Operation\SaveOperation;
use Icybee\Modules\Users\User;

chdir(__DIR__);

$_SERVER['DOCUMENT_ROOT'] = __DIR__;

require __DIR__ . '/../vendor/autoload.php';

function get_sandbox_directory()
{
	return __DIR__ . DIRECTORY_SEPARATOR . 'sandbox';
}

/**
 * Create a new file record.
 *
 * @param string $src Absolute path to the source file.
 * @param array $attributes Additional record attributes.
 *
 * @return File
 */
function create_file_record($src, array $attributes=[])
{
	$app = \ICanBoogie\app();

	/* @var $user User */

	$user = $app->models['users'][1];
	$user->login();

	$pathname = $app->config[AppConfig::REPOSITORY_TMP] . DIRECTORY_SEPARATOR . basename($src);

	copy($src, $pathname);

	$request = Request::from([

		'is_post' => true,

		'request_params' => [

			Operation::DESTINATION => 'files',
			Operation::NAME => 'save'

		] + $attributes,

		'files' => [

			SaveOperation::USERFILE => [ 'pathname' => $pathname ]

		]

	]);

	$operation = new FakeSaveOperation;
	$operation($request);

	$user->logout();

	return $operation->record;
}

/**
 * @param string $extension
 *
 * @return string
 */
function create_file($extension = '')
{
	$filename = \ICanBoogie\generate_v4_uuid() . $extension;
	$pathname = get_sandbox_directory() . DIRECTORY_SEPARATOR . $filename;

	file_put_contents($pathname, openssl_random_pseudo_bytes(10000));

	return $pathname;
}

$app = boot();
$app->modules->install();

// so we don't have to deal with the website
Prototype::from(Core::class)['get_site_id'] = function() {

	return 0;

};

User::from([

	User::USERNAME => 'admin',
	User::EMAIL => 'admin@example.com',
	User::TIMEZONE => 'Europe/Paris'

])->save();
