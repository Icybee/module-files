<?php

namespace Icybee\Modules\Files\Block\ManageBlock;

use Icybee\Block\ManageBlock\Column;
use Icybee\Block\ManageBlock\FilterDecorator;

/**
 * Representation of the `mime` column.
 */
class MimeColumn extends Column
{
	public function render_cell($record)
	{
		return new FilterDecorator($record, $this->id, $this->manager->is_filtering($this->id));
	}
}
