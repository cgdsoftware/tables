<?php

namespace LaravelEnso\Tables\App\Attributes;

class Column
{
    public const Mandatory = ['data', 'label', 'name'];

    public const Optional = [
        'align', 'class', 'dateFormat', 'enum', 'meta', 'money', 'tooltip', 'resource',
    ];

    public const Meta = [
        'boolean', 'clickable', 'cents', 'customTotal', 'date', 'datetime', 'icon',
        'notExportable', 'nullLast', 'searchable', 'rawTotal', 'rogue', 'slot',
        'sortable', 'sort:ASC', 'sort:DESC', 'translatable', 'total',
    ];
}
