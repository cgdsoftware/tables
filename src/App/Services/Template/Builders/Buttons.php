<?php

namespace LaravelEnso\Tables\App\Services\Template\Builders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use LaravelEnso\Helpers\App\Classes\Obj;

class Buttons
{
    private const PathActions = ['href', 'ajax', 'export', 'action'];

    private Obj $template;
    private Obj $defaults;

    public function __construct(Obj $template)
    {
        $this->template = $template;
        $this->defaults = new Obj(Config::get('enso.tables.buttons'));
        $this->template->set('actions', false);
    }

    public function build(): void
    {
        $buttons = $this->buttons();

        $this->template->set('buttons', $buttons);
        $this->template->set('actions', $buttons->get('row')->isNotEmpty());
    }

    private function buttons(): Obj
    {
        return $this->template->get('buttons')
            ->reduce(fn ($buttons, $button) => $this
                ->add($buttons, $button), new Obj(['global' => [], 'row' => []]));
    }

    private function add($buttons, $button): Collection
    {
        [$button, $type] = is_string($button)
            ? $this->default($button)
            : [$button, $button->get('type')];

        if ($this->shouldDisplayButton($button, $type)) {
            $button->forget(['fullRoute', 'routeSuffix']);
            $buttons[$type]->push($button);
        }

        return $buttons;
    }

    private function shouldDisplayButton(Obj $button, string $type)
    {
        return ! $button->has('action')
            && ! $button->has('route')
            && ! $button->has('routeSuffix')
            || $this->actionComputingSuccedes($button, $type);
    }

    private function default($button): array
    {
        return $this->defaults->get('global')->keys()->contains($button)
            ? [$this->defaults->get('global')->get($button), 'global']
            : [$this->defaults->get('row')->get($button), 'row'];
    }

    private function actionComputingSuccedes($button, $type): bool
    {
        $route = $this->route($button);

        if ($this->routeIsForbidden($route)) {
            return false;
        }

        $this->pathOrRoute($button, $route, $type);

        return true;
    }

    private function pathOrRoute($button, $route, $type)
    {
        if ((new Collection(self::PathActions))->contains($button->get('action'))) {
            $param = $type === 'row' ? 'dtRowId' : null;
            $button->set('path', route($route, [$param], false));

            return;
        }

        $button->set('route', $route);
    }

    private function route($button): ?string
    {
        if (
            $button->has('fullRoute')
            && $button->get('fullRoute') !== null
        ) {
            return $button->get('fullRoute');
        }

        return $button->has('routeSuffix')
            && $button->get('routeSuffix') !== null
            ? $this->template->get('routePrefix')
            .'.'.$button->get('routeSuffix')
            : null;
    }

    private function routeIsForbidden($route): bool
    {
        return $this->needAuthorization()
            && Auth::user()->cannot('access-route', $route);
    }

    private function needAuthorization()
    {
        return ! empty(Config::get('enso.config'))
            && $this->template->get('auth') !== false;
    }
}
