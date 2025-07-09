<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Table extends Component
{
    public $url;
    public $id;
    public $columns;
    public $items;
    public $actions;
    /**
     * Create a new component instance.
     */
    public function __construct($url , $id, $columns, $items, $actions = null)
    {
        $this->url = $url;
        $this->id = $id;
        $this->columns = $columns;
        $this->items = $items;
        $this->actions = $actions;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.table');
    }
}
