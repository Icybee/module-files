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

if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'repository'))
{
	mkdir(__DIR__ . DIRECTORY_SEPARATOR . 'repository');
}

require __DIR__ . '/../vendor/autoload.php';

/**
 * A save operation that doesn't require form validation.
 */
class FakeSaveOperation extends SaveOperation
{
	public function __invoke(Request $request)
	{
		$this->module = $this->app->modules['files'];

		return parent::__invoke($request);
	}

	protected function get_controls()
	{
		return [

			self::CONTROL_FORM => false

		] + parent::get_controls();
	}
}

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
	$response = $operation($request);

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

$errors = $app->modules->install(new \ICanBoogie\Errors());

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

$user = User::from([

	'username' => 'admin',
	'email' => 'admin@example.com'

]);

$app->site_id = 0;

$user->save();
