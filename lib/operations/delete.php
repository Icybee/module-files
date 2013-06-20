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

/**
 * Deletes a file.
 *
 * Because a same file may actually be used by multiple records, the operation actually only
 * deletes the record. The module attaches an event hook to the `process` event of the operation
 * to remove unused files.
 */
class DeleteOperation extends \Icybee\Modules\Nodes\DeleteOperation
{

}