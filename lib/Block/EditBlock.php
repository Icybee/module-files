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

use ICanBoogie\I18n;
use ICanBoogie\Operation;

use Brickrouge\Element;
use Brickrouge\Document;
use Brickrouge\Form;

use Icybee\Modules\Files as Root;
use Icybee\Modules\Files\File;

/**
 * @property File $record
 */
class EditBlock extends \Icybee\Modules\Nodes\Block\EditBlock
{
	const ACCEPT = '#files-accept';
	const UPLOADER_CLASS = 'uploader class';

	protected $accept = null;
	protected $uploader_class = 'Icybee\Modules\Files\FileUpload';

	static protected function add_assets(Document $document)
	{
		parent::add_assets($document);

		$document->css->add(Root\DIR . 'public/edit.css');
		$document->js->add(Root\DIR . 'public/edit.js');
	}

	protected function lazy_get_values()
	{
		return parent::lazy_get_values() + [

			File::NID => null,
			File::PATH => null

		];
	}

	protected function lazy_get_children()
	{
		$folder = \ICanBoogie\REPOSITORY . 'tmp';

		if (!is_writable($folder))
		{
			return [

				Element::CHILDREN => [

					$this->t('The folder %folder is not writable !', [ '%folder' => $folder ])

				]
			];
		}

		$properties = $this->values;
		$nid = $properties[File::NID];
		$path = \ICanBoogie\strip_root($properties[File::PATH]);

		if (!$path && $this->record)
		{
			$path = $this->record->pathname->relative;
		}

		$this->attributes = \ICanBoogie\array_merge_recursive($this->attributes, [

			Form::HIDDENS => [

				File::PATH => $path

			],

			Form::VALUES => [

				File::PATH => $path

			]

		]);

		$options = [

			self::ACCEPT => $this->accept,
			self::UPLOADER_CLASS => $this->uploader_class

		];

		$uploader_class = $options[self::UPLOADER_CLASS];

		return array_merge(parent::lazy_get_children(), [

			File::PATH => new $uploader_class([

				Form::LABEL => 'file',
				Element::REQUIRED => empty($nid),
				\Brickrouge\File::FILE_WITH_LIMIT => $this->app->site->metas[$this->module->flat_id . '.max_file_size'],
				Element::WEIGHT => -100,
				\Brickrouge\File::T_UPLOAD_URL => Operation::encode($this->module->id . '/upload'),

				'value' => $path

			]),

			File::DESCRIPTION => $this->app->editors['rte']->from([

				Form::LABEL => 'description',
				Element::WEIGHT => 50,

				'rows' => 5

			])
		]);
	}
}
