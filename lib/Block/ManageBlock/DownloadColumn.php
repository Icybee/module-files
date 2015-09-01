<?php

namespace Icybee\Modules\Files\Block\ManageBlock;

use Brickrouge\A;
use Icybee\Block\ManageBlock;
use Icybee\Modules\Files\File;

/**
 * Representation of the `download` column.
 */
class DownloadColumn extends ManageBlock\Column
{
	public function __construct(ManageBlock $manager, $id, array $options=[])
	{
		parent::__construct($manager, $id, [

			'orderable' => false,
			'title' => null

		]);
	}

	/**
	 * @param File $record
	 *
	 * @inheritdoc
	 */
	public function render_cell($record)
	{
		return new A('', $record->url('download'), [

			'class' => 'download',
			'title' => $this->manager->t('Download file')

		]);
	}
}
