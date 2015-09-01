<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Files\Block;

use Brickrouge\Document;

use Icybee\Modules\Files as Root;
use Icybee\Modules\Files\File;
use Icybee\Modules\Files\Module;

/**
 * A block to manage files.
 */
class ManageBlock extends \Icybee\Modules\Nodes\Block\ManageBlock
{
	static protected function add_assets(Document $document)
	{
		parent::add_assets($document);

		$document->css->add(Root\DIR . '/public/manage.css');
	}

	public function __construct(Module $module, array $attributes)
	{
		parent::__construct($module, $attributes + [

			self::T_COLUMNS_ORDER => [ 'title', 'size', 'download', 'is_online', 'uid', 'mime', 'updated_at' ]

		]);
	}

	/**
	 * Adds the following columns:
	 *
	 * - `mime`: An instance of {@link ManageBlock\MimeColumn}.
	 * - `size`: An instance of {@link ManageBlock\SizeColumn}.
	 * - `download`: An instance of {@link ManageBlock\DownloadColumn}.
	 *
	 * @return array
	 */
	protected function get_available_columns()
	{
		return array_merge(parent::get_available_columns(), [

			File::MIME => ManageBlock\MimeColumn::class,
			File::SIZE => ManageBlock\SizeColumn::class,
			'download' => ManageBlock\DownloadColumn::class

		]);
	}
}
