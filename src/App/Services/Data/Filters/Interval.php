<?php

namespace LaravelEnso\Tables\App\Services\Data\Filters;

use Illuminate\Support\Collection;

class Interval extends BaseFilter
{
    public function applies(): bool
    {
        return $this->config->intervals()->first(fn ($interval) => $interval
            ->first(fn ($value) => $this->isValid($value->get('min'))
                || $this->isValid($value->get('max'))) !== null
            ) !== null;
    }

    public function handle(): void
    {
        $this->query->where(fn () => $this->config->intervals()
            ->each(fn ($interval, $table) => (new Collection($interval))
                ->each(fn ($value, $column) => $this
                    ->limit($table, $column, $value, 'min', '>=')
                    ->limit($table, $column, $value, 'max', '<=')))
        );
    }

    private function limit($table, $column, $value, $bound, $operator): self
    {
        if ($this->isValid($value->get($bound))) {
            $this->query->where(
                $table.'.'.$column, $operator, $value->get($bound)
            );
        }

        return $this;
    }

    private function isValid($value): bool
    {
        return ! (new Collection([null, '']))->containsStrict($value);
    }
}
