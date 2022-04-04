<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Base\AjaxForms;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\DataSrc\Retenciones;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Model\Base\TransformerDocument;
use FacturaScripts\Dinamic\Model\Variante;

trait CommonLineHTML
{
    private static function codimpuesto(Translator $i18n, string $idlinea, BusinessDocumentLine $line, TransformerDocument $model, string $jsFunc): string
    {
        $options = [];
        foreach (Impuestos::all() as $imp) {
            $options[] = $line->iva == $imp->iva ?
                '<option value="' . $imp->iva . '" selected="">' . $imp->descripcion . '</option>' :
                '<option value="' . $imp->iva . '">' . $imp->descripcion . '</option>';
        }

        $attributes = $model->editable && false === $line->suplido ?
            'name="iva_' . $idlinea . '" onchange="return ' . $jsFunc . '(\'recalculate-line\', \'0\');"' :
            'disabled=""';
        return '<div class="col-sm col-md col-lg-1 px-0 order-6">'
            . '<div class="mb-1 small"><span class="d-lg-none">' . $i18n->trans('tax') . '</span>'
            . '<select ' . $attributes . ' class="form-control form-control-sm rounded-0">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    protected static function descripcion(Translator $i18n, string $idlinea, BusinessDocumentLine $line, TransformerDocument $model): string
    {
        $attributes = $model->editable ? 'name="descripcion_' . $idlinea . '"' : 'disabled=""';

        $rows = 0;
        foreach (explode("\n", $line->descripcion) as $desLine) {
            $rows += mb_strlen($desLine) < 140 ? 1 : ceil(mb_strlen($desLine) / 140);
        }

        $columnMd = empty($line->referencia) ? 12 : 8;
        $columnSm = empty($line->referencia) ? 10 : 8;
        return '<div class="col-sm-' . $columnSm . ' col-md-' . $columnMd . ' col-lg px-0 order-2">'
            . '<div class="mb-1 small"><span class="d-lg-none">' . $i18n->trans('description') . '</span>'
            . '<textarea ' . $attributes . ' class="form-control form-control-sm rounded-0 doc-line-desc" rows="' . $rows . '">' . $line->descripcion . '</textarea>'
            . '</div>'
            . '</div>';
    }

    protected static function referencia(Translator $i18n, string $idlinea, BusinessDocumentLine $line, TransformerDocument $model): string
    {
        $sortable = $model->editable ?
            '<input type="hidden" name="orden_' . $idlinea . '" value="' . $line->orden . '"/>' :
            '';

        $variante = new Variante();
        $where = [new DataBaseWhere('referencia', $line->referencia)];
        if (empty($line->referencia) || false === $variante->loadFromCode('', $where)) {
            return '<div class="d-none d-lg-block col-sm-2 col-md-2 col-lg-1 px-0 order-1">'
                . $sortable
                . '</div>';
        }

        return '<div class="col-sm-2 col-md-2 col-lg-1 pl-0 text-break align-self-start order-1">'
            . '<div class="mb-1 small"><div class="d-lg-none text-truncate">' . $i18n->trans('reference') . '</div>'
            . $sortable . '<a href="' . $variante->url() . '">' . $line->referencia . '</a>'
            . '<input type="hidden" name="referencia_' . $idlinea . '" value="' . $line->referencia . '"/>'
            . '</div>'
            . '</div>';
    }

    protected static function dtopor(Translator $i18n, string $idlinea, BusinessDocumentLine $line, TransformerDocument $model, string $jsFunc): string
    {
        $attributes = $model->editable ?
            'name="dtopor_' . $idlinea . '" min="0" max="100" step="1" onkeyup="return ' . $jsFunc . '(\'recalculate-line\', \'0\');"' :
            'disabled=""';
        return '<div class="col-sm col-md col-lg-1 px-0 order-5">'
            . '<div class="mb-1 small"><span class="d-lg-none">' . $i18n->trans('percentage-discount') . '</span>'
            . '<input type="number" ' . $attributes . ' value="' . $line->dtopor . '" class="form-control form-control-sm rounded-0"/>'
            . '</div>'
            . '</div>';
    }

    protected static function dtopor2(Translator $i18n, string $idlinea, BusinessDocumentLine $line, TransformerDocument $model, string $field, string $jsFunc): string
    {
        $attributes = $model->editable ?
            'name="' . $field . '_' . $idlinea . '" min="0" max="100" step="1" onkeyup="return ' . $jsFunc . '(\'recalculate-line\', \'0\');"' :
            'disabled=""';
        return '<div class="col">'
            . '<div class="mb-2">' . $i18n->trans('percentage-discount') . ' 2'
            . '<input type="number" ' . $attributes . ' value="' . $line->{$field} . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    protected static function irpf(Translator $i18n, string $idlinea, BusinessDocumentLine $line, TransformerDocument $model, string $jsFunc): string
    {
        if ($line->suplido) {
            return '';
        }

        $options = ['<option value="">------</option>'];
        foreach (Retenciones::all() as $ret) {
            $options[] = $line->irpf === $ret->porcentaje ?
                '<option value="' . $ret->porcentaje . '" selected="">' . $ret->descripcion . '</option>' :
                '<option value="' . $ret->porcentaje . '">' . $ret->descripcion . '</option>';
        }

        $attributes = $model->editable ?
            'name="irpf_' . $idlinea . '" onkeyup="return ' . $jsFunc . '(\'recalculate-line\', \'0\');"' :
            'disabled=""';
        return '<div class="col">'
            . '<div class="mb-2">' . $i18n->trans('retention')
            . '<select ' . $attributes . ' class="form-control">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    protected static function lineTotal(Translator $i18n, string $idlinea, BusinessDocumentLine $line, TransformerDocument $model): string
    {
        $total = $line->pvptotal * (100 + $line->iva + $line->recargo - $line->irpf) / 100;
        return '<div class="col-sm col-md col-lg-1 px-0 order-7">'
            . '<div class="mb-1 small"><span class="d-lg-none">' . $i18n->trans('subtotal') . '</span>'
            . '<input type="number" name="linetotal_' . $idlinea . '"  value="' . $total . '" class="form-control form-control-sm rounded-0" readonly/>'
            . '</div>'
            . '</div>';
    }

    protected static function recargo(Translator $i18n, string $idlinea, BusinessDocumentLine $line, TransformerDocument $model, string $jsFunc): string
    {
        if ($line->suplido) {
            return '';
        }

        $attributes = $model->editable ?
            'name="recargo_' . $idlinea . '" min="0" max="100" step="1" onkeyup="return ' . $jsFunc . '(\'recalculate-line\', \'0\');"' :
            'disabled=""';
        return '<div class="col">'
            . '<div class="mb-2">' . $i18n->trans('percentage-surcharge')
            . '<input type="number" ' . $attributes . ' value="' . $line->recargo . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    protected static function renderCalculatorBtn(Translator $i18n, string $idlinea, TransformerDocument $model, string $jsName): string
    {
        if ($model->editable === false) {
            return '';
        }

        return '<div class="col-auto col-sm-auto px-0 order-8">'
            . '<button class="btn btn-outline-secondary btn-sm rounded-0 mb-1" type="button"'
            . ' onclick="' . $jsName . '(\'' . $idlinea . '\')"><i class="fas fa-calculator"></i></button>'
            . '</div>';
    }

    protected static function renderExpandButton(Translator $i18n, string $idlinea, TransformerDocument $model, string $jsName): string
    {
        if ($model->editable) {
            return '<div class="col-auto col-sm-auto px-0 order-9">'
                . '<button type="button" data-toggle="modal" data-target="#lineModal-' . $idlinea . '" class="btn btn-sm btn-outline-secondary rounded-0 mb-1" title="'
                . $i18n->trans('more') . '"><i class="fas fa-ellipsis-h"></i></button>'
                . '<button class="btn btn-sm btn-outline-danger btn-spin-action rounded-0 mb-1" type="button" title="' . $i18n->trans('delete') . '"'
                . ' onclick="return ' . $jsName . '(\'rm-line\', \'' . $idlinea . '\');">'
                . '<i class="fas fa-trash-alt"></i></button>'
                . '</div>';
        }

        return '<div class="col-auto col-sm-auto px-0 order-9"><button type="button" data-toggle="modal" data-target="#lineModal-' . $idlinea . '" class="btn btn-sm btn-outline-secondary rounded-0 mb-1" title="'
            . $i18n->trans('more') . '"><i class="fas fa-ellipsis-h"></i></button></div>';
    }

    protected static function suplido(Translator $i18n, string $idlinea, BusinessDocumentLine $line, TransformerDocument $model, string $jsFunc): string
    {
        $attributes = $model->editable ?
            'name="suplido_' . $idlinea . '" onkeyup="return ' . $jsFunc . '(\'recalculate-line\', \'0\');"' :
            'disabled=""';
        $options = $line->suplido ?
            ['<option value="0">' . $i18n->trans('no') . '</option>', '<option value="1" selected="">' . $i18n->trans('yes') . '</option>'] :
            ['<option value="0" selected="">' . $i18n->trans('no') . '</option>', '<option value="1">' . $i18n->trans('yes') . '</option>'];
        return '<div class="col">'
            . '<div class="mb-2">' . $i18n->trans('supplied')
            . '<select ' . $attributes . ' class="form-control">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }
}