<?php

use Livewire\Volt\Actions;
use Livewire\Volt\CompileContext;
use Livewire\Volt\Contracts\Compiled;
use Livewire\Volt\Component;

new class extends Component implements Livewire\Volt\Contracts\FunctionalComponent
{
    public static CompileContext $__context;

    use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public $surat_jalan_no;

    public $tanggal;

    public $lokasi;

    public $is_manual_lokasi;

    public $pemohon;

    public $petugas;

    public $penerima;

    public $no_polisi;

    public $pelaksana_kecamatan;

    public $keterangan;

    public $selected_materials;

    public function mount()
    {
        (new Actions\InitializeState)->execute(static::$__context, $this, get_defined_vars());

        (new Actions\CallHook('mount'))->execute(static::$__context, $this, get_defined_vars());
    }

    #[\Livewire\Attributes\Computed()]
    public function remainingQuotas()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('remainingQuotas'))->execute(...$arguments);
    }

    #[\Livewire\Attributes\Computed()]
    public function categories()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('categories'))->execute(...$arguments);
    }

    #[\Livewire\Attributes\Computed()]
    public function allMaterials()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('allMaterials'))->execute(...$arguments);
    }

    #[\Livewire\Attributes\Computed()]
    public function rabs()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('rabs'))->execute(...$arguments);
    }

    public function addMaterial()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('addMaterial'))->execute(...$arguments);
    }

    public function removeMaterial($index)
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('removeMaterial'))->execute(...$arguments);
    }

    public function save()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('save'))->execute(...$arguments);
    }

};