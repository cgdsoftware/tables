<?php

namespace LaravelEnso\Tables\App\Services\Data\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use LaravelEnso\Tables\App\Contracts\Table;
use LaravelEnso\Tables\App\Services\Data\ArrayComputors;
use LaravelEnso\Tables\App\Services\Data\Config;
use LaravelEnso\Tables\App\Services\Data\Filters;
use LaravelEnso\Tables\App\Services\Data\ModelComputors;
use LaravelEnso\Tables\App\Services\Data\Sort;

class Data
{
    private Config $config;
    private Table $table;
    private Builder $query;
    private Collection $data;

    public function __construct(Table $table, Config $config)
    {
        $this->table = $table;
        $this->config = $config;
        $this->query = $this->table->query();
    }

    public function build(): self
    {
        $this->filter()
            ->sort()
            ->limit()
            ->setData();

        if ($this->data->isNotEmpty()) {
            $this->appends()
                ->modelCompute()
                ->sanitize()
                ->arrayCompute()
                ->strip()
                ->flatten();
        }

        return $this;
    }

    public function toArray(): array
    {
        return ['data' => $this->data()];
    }

    public function data(): Collection
    {
        $this->build();

        return $this->data;
    }

    private function filter(): self
    {
        (new Filters($this->table, $this->config, $this->query))->handle();

        return $this;
    }

    private function sort(): self
    {
        if ($this->config->meta()->get('sort')) {
            (new Sort($this->config, $this->query))->handle();
        }

        return $this;
    }

    private function limit(): self
    {
        $this->query->skip($this->config->meta()->get('start'))
            ->take($this->config->meta()->get('length'));

        return $this;
    }

    private function setData(): self
    {
        $this->data = $this->query->get();

        return $this;
    }

    private function appends(): self
    {
        if ($this->config->filled('appends')) {
            $this->data->each->setAppends(
                $this->config->get('appends')->toArray()
            );
        }

        return $this;
    }

    private function modelCompute(): self
    {
        $this->data = ModelComputors::handle($this->config, $this->data);

        return $this;
    }

    private function sanitize(): self
    {
        $this->data = new Collection($this->data->toArray());

        return $this;
    }

    private function arrayCompute(): self
    {
        $this->data = ArrayComputors::handle($this->config, $this->data);

        return $this;
    }

    private function strip(): self
    {
        if (! $this->config->filled('strip')) {
            return $this;
        }

        $this->data = $this->data->map(function ($row) {
            foreach ($this->config->get('strip')->toArray() as $attr) {
                unset($row[$attr]);
            }

            return $row;
        });

        return $this;
    }

    private function flatten(): void
    {
        if ($this->config->get('flatten')) {
            $this->data = $this->data
                ->map(fn ($record) => Arr::dot($record));
        }
    }
}
