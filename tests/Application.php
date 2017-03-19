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

use Icybee\Modules;

class Application extends Core
{
	use Modules\Files\Binding\ApplicationBindings;
	use Modules\Users\Binding\ApplicationBindings;
}
