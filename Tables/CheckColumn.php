<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 30/01/15 12:21
 */

namespace Modules\Admin\Tables;

use Mindy\Table\Columns\Column;

class CheckColumn extends Column
{
    /**
     * @var string
     */
    public $headCellTemplate = '<th {html}>{out}</th>';
    /**
     * @var array
     */
    public $html = [
        'class' => 'check',
        'align' => 'left'
    ];
    /**
     * @var bool
     */
    public $virtual = true;
    /**
     * @var int count of objects in table
     */
    public $length = 0;

    public function getOut()
    {
        return strtr('<input type="checkbox"{disabled} id="check-all"/><label class="checkbox-state" for="check-all"></label>', [
            '{disabled}' => $this->length == 0 ? ' disabled="disabled"' : ''
        ]);
    }

    public function renderHeadCell()
    {
        return strtr($this->headCellTemplate, [
            '{out}' => $this->getOut(),
            '{html}' => $this->formatHtmlAttributes([
                'class' => 'check all'
            ])
        ]);
    }

    public function getValue($record)
    {
        return strtr('<input type="checkbox" name="models[]" id="check-{pk}" value="{pk}" /><label class="checkbox-state" for="check-{pk}"></label>', [
            '{pk}' => $record->pk
        ]);
    }
}
