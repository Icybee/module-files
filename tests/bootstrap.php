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

use ICanBoogie\Core;
use ICanBoogie\HTTP\Request;
use ICanBoogie\Operation;

use Icybee\Modules\Files\Operation\SaveOperation;
use Icybee\Modules\Users\User;

$_SERVER['DOCUMENT_ROOT'] = __DIR__;

$autoload = require __DIR__ . '/../vendor/autoload.php';
$autoload->addPsr4('Icybee\Modules\Files\\', __DIR__);

/**
 * Create a new file record.
 *
 * @param string $src Absolute path to the source file.
 * @param array $attributes Additional record attributes.
 *
 * @return File
 */
function create_file($src, array $attributes=[])
{
	$app = \ICanBoogie\app();

	/* @var $user User */

	$user = $app->models['users'][1];
	$user->login();

	$pathname = \ICanBoogie\REPOSITORY . 'tmp' . DIRECTORY_SEPARATOR. basename($src);

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

/* @var $app Core|\ICanBoogie\Module\CoreBindings|\Icybee\Modules\Sites\Binding\CoreBindings */

$app = new Core(\ICanBoogie\array_merge_recursive(\ICanBoogie\get_autoconfig(), [

	'config-path' => [

		__DIR__ . DIRECTORY_SEPARATOR . 'config' => 10

	],

	'module-path' => [

		realpath(__DIR__ . '/../')

	]

]));

$app->boot();
$app->modules->install();
$app->site_id = 0; // so we don't have to deal with the website

User::from([ 'username' => 'admin', 'email' => 'admin@example.com' ])->save();
