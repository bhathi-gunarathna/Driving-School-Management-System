<?php

namespace App\Livewire\Admin\Coaches;

use Livewire\Component;

class View extends Component
{
    public function render()
    {
        return view('livewire.admin.coaches.edit')
        ->layout('layouts.admin');
    }
}
