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

    public $editingUser;

    public $name;

    public $email;

    public $password;

    public $selected_role;

    public function mount()
    {
        (new Actions\InitializeState)->execute(static::$__context, $this, get_defined_vars());

        (new Actions\CallHook('mount'))->execute(static::$__context, $this, get_defined_vars());
    }

    #[\Livewire\Attributes\Computed()]
    public function users()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('users'))->execute(...$arguments);
    }

    #[\Livewire\Attributes\Computed()]
    public function roles()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('roles'))->execute(...$arguments);
    }

    public function openCreate()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('openCreate'))->execute(...$arguments);
    }

    public function saveUser()
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('saveUser'))->execute(...$arguments);
    }

    public function editUser(\App\Models\User $user)
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('editUser'))->execute(...$arguments);
    }

    public function deleteUser(\App\Models\User $user)
    {
        $arguments = [static::$__context, $this, func_get_args()];

        return (new Actions\CallMethod('deleteUser'))->execute(...$arguments);
    }

};