<?php

namespace Filament\Schemas\Components;

class View
{
    /**
     * @var list<string>
     */
    public array $hiddenOperations = [];

    public function __construct(public string $viewName) {}

    public static function make(string $view): static
    {
        return new static($view);
    }

    /**
     * @param  string|array<int, string>  $operations
     */
    public function hiddenOn(string|array $operations): static
    {
        $this->hiddenOperations = array_map(strval(...), (array) $operations);

        return $this;
    }

    public function getView(): string
    {
        return $this->viewName;
    }
}
