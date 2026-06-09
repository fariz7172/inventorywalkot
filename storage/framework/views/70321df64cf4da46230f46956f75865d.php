<?php

use Livewire\Volt\Actions;
use Livewire\Volt\CompileContext;
use Livewire\Volt\Contracts\Compiled;
use Livewire\Volt\Component;

new class extends Component implements Livewire\Volt\Contracts\FunctionalComponent
{
    public static CompileContext $__context;

    use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public $showModal;

    public $editingCategory;

    public $name;

    public $description;

    public $search;

    public function mount()
    {
        (new Actions\InitializeState)->execute(static::$__context, $this, get_defined_vars());

        (new Actions\CallHook('mount'))->execute(static::$__context, $this, get_defined_vars());
    }

    #[\Livewire\Attributes\Computed()]
    public function categories()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('categories'))->execute(...$arguments);
    }

    public function openCreate()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('openCreate'))->execute(...$arguments);
    }

    public function save()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('save'))->execute(...$arguments);
    }

    public function edit(\App\Models\Category $category)
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('edit'))->execute(...$arguments);
    }

    public function delete(\App\Models\Category $category)
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('delete'))->execute(...$arguments);
    }

    public function getListeners()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\ResolveListeners)->execute(...$arguments);
    }

    public function globalSearchHandler($search)
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallListener('global-search'))->execute(...$arguments);
    }

};