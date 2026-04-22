<?php

use Livewire\Volt\Actions;
use Livewire\Volt\CompileContext;
use Livewire\Volt\Contracts\Compiled;
use Livewire\Volt\Component;

new class extends Component implements Livewire\Volt\Contracts\FunctionalComponent
{
    public static CompileContext $__context;

    use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public $showCreateModal;

    public $showDetailModal;

    public $isEditing;

    public $selectedOpnameId;

    public $opnames;

    public $materials;

    public $opname_date;

    public $notes;

    public $approverNotes;

    public $opnameItems;

    public $itemNotes;

    public $selectedOpname;

    public $hasPendingOpname;

    public $needsDifferenceConfirmation;

    public $filterPeriod;

    public function mount()
    {
        (new Actions\InitializeState)->execute(static::$__context, $this, get_defined_vars());

        (new Actions\CallHook('mount'))->execute(static::$__context, $this, get_defined_vars());
    }

    public function loadData()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('loadData'))->execute(...$arguments);
    }

    public function openCreate()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('openCreate'))->execute(...$arguments);
    }

    public function editOpname($id)
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('editOpname'))->execute(...$arguments);
    }

    public function deleteOpname($id)
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('deleteOpname'))->execute(...$arguments);
    }

    public function saveOpname($force = false)
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('saveOpname'))->execute(...$arguments);
    }

    public function openDetail($id)
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('openDetail'))->execute(...$arguments);
    }

    public function closeDetail()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('closeDetail'))->execute(...$arguments);
    }

    public function approveOpname($id)
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('approveOpname'))->execute(...$arguments);
    }

    public function rejectOpname($id)
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('rejectOpname'))->execute(...$arguments);
    }

};