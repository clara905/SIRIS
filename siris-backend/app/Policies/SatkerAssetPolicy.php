<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class SatkerAssetPolicy
{
    public function view(User $user, Asset $asset): bool
    {
        return $user->hasRole('satker') && $user->is_active && $user->bidang_id && $user->sub_bidang_id && $user->satker_id
            && (int) $asset->bidang_id === (int) $user->bidang_id
            && (int) $asset->sub_bidang_id === (int) $user->sub_bidang_id;
    }

    public function update(User $user, Asset $asset): bool
    {
        return $this->view($user, $asset) && (int) $asset->satker_id === (int) $user->satker_id && (int) $asset->created_by === (int) $user->id;
    }

    public function delete(User $user, Asset $asset): bool
    {
        return false;
    }
}
