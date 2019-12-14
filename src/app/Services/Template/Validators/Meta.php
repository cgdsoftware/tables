<?php

namespace LaravelEnso\Tables\app\Services\Template\Validators;

use Illuminate\Support\Str;
use LaravelEnso\Helpers\app\Classes\Obj;
use LaravelEnso\Tables\app\Attributes\Column as Attributes;
use LaravelEnso\Tables\app\Exceptions\Meta as Exception;

class Meta
{
    public static function validate(Obj $column)
    {
        $attributes = $column->get('meta');

        $diff = $attributes->diff(Attributes::Meta);

        if ($diff->isNotEmpty()) {
            throw Exception::unknownAttributes(
                $diff->implode('", "')
            );
        }

        if (Str::contains($column->get('name'), '.')
            && ($attributes->contains('sortable'))) {
            throw Exception::unsupported(
                $column->get('name')
            );
        }
    }
}
