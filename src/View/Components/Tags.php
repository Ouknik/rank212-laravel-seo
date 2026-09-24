<?php

namespace SeoSaas\LaravelSeo\View\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;
use Illuminate\View\View;

class Tags extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?Model $model = null
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('seo::components.tags', [
            'model' => $this->model,
        ]);
    }
}
