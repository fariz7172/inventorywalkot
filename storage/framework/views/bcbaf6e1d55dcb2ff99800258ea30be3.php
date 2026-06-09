<?php

use Livewire\Volt\Actions;
use Livewire\Volt\CompileContext;
use Livewire\Volt\Contracts\Compiled;
use Livewire\Volt\Component;

new class extends Component implements Livewire\Volt\Contracts\FunctionalComponent
{
    public static CompileContext $__context;

    use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    use Livewire\WithFileUploads;

    public $importFile;

    public $showModal;

    public $editingRab;

    public $lokasi;

    public $kecamatan_id;

    public $search;

    public $showMaterialModal;

    public $isViewOnly;

    public $managingRab;

    public $rabMaterials;

    public function mount()
    {
        (new Actions\InitializeState)->execute(static::$__context, $this, get_defined_vars());

        (new Actions\CallHook('mount'))->execute(static::$__context, $this, get_defined_vars());
    }

    #[\Livewire\Attributes\Computed()]
    public function rabs()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('rabs'))->execute(...$arguments);
    }

    #[\Livewire\Attributes\Computed()]
    public function allMaterials()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('allMaterials'))->execute(...$arguments);
    }

    #[\Livewire\Attributes\Computed()]
    public function allKecamatans()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('allKecamatans'))->execute(...$arguments);
    }

    public function openCreate()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('openCreate'))->execute(...$arguments);
    }

    public function closeModal()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('closeModal'))->execute(...$arguments);
    }

    public function closeMaterialModal()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('closeMaterialModal'))->execute(...$arguments);
    }

    public function save()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('save'))->execute(...$arguments);
    }

    public function edit(\App\Models\Rab $rab)
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('edit'))->execute(...$arguments);
    }

    public function delete(\App\Models\Rab $rab)
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('delete'))->execute(...$arguments);
    }

    public function toggleLock(\App\Models\Rab $rab)
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('toggleLock'))->execute(...$arguments);
    }

    public function downloadTemplate()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('downloadTemplate'))->execute(...$arguments);
    }

    public function importExcel()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('importExcel'))->execute(...$arguments);
    }

    public function openManageMaterial(\App\Models\Rab $rab)
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('openManageMaterial'))->execute(...$arguments);
    }

    public function addRabMaterial()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('addRabMaterial'))->execute(...$arguments);
    }

    public function removeRabMaterial($index)
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('removeRabMaterial'))->execute(...$arguments);
    }

    public function saveMaterials()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('saveMaterials'))->execute(...$arguments);
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