<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * @param  string|null  $title  Page name for the browser tab / app switcher;
     *                              the app name is appended by the head partial.
     * @param  string|null  $heading  On-page <h2> when it should differ from
     *                                $title; pages needing markup in the heading
     *                                pass a `header` slot instead.
     */
    public function __construct(public ?string $title = null, public ?string $heading = null) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
