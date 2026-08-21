<?php

use Livewire\Volt\Actions;
use Livewire\Volt\CompileContext;
use Livewire\Volt\Contracts\Compiled;
use Livewire\Volt\Component;

new class extends Component implements Livewire\Volt\Contracts\FunctionalComponent
{
    public static CompileContext $__context;

    use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public $selectedKecamatanId;

    public $selectedRabId;

    public $selectedMonth;

    public $selectedYear;

    public function mount()
    {
        (new Actions\InitializeState)->execute(static::$__context, $this, get_defined_vars());

        (new Actions\CallHook('mount'))->execute(static::$__context, $this, get_defined_vars());
    }

    #[\Livewire\Attributes\Computed()]
    public function kecamatans()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('kecamatans'))->execute(...$arguments);
    }

    #[\Livewire\Attributes\Computed()]
    public function rabs()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('rabs'))->execute(...$arguments);
    }

    public function updatedSelectedKecamatanId()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('updatedSelectedKecamatanId'))->execute(...$arguments);
    }

    #[\Livewire\Attributes\Computed()]
    public function years()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('years'))->execute(...$arguments);
    }

    #[\Livewire\Attributes\Computed()]
    public function reportData()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('reportData'))->execute(...$arguments);
    }

    public function exportExcel()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('exportExcel'))->execute(...$arguments);
    }

};