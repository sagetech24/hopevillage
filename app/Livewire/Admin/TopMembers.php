<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;

class TopMembers extends Component
{
    public function placeholder()
    {
        return view('livewire.admin.partials.card-placeholder');
    }

    public function getTopMembersProperty()
    {
        return User::where('user_type', 'member')
            ->orderBy('total_points', 'desc')
            ->take(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.top-members', [
            'topMembers' => $this->topMembers,
        ]);
    }
}
