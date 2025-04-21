<?php

namespace App\Policies;

use App\Models\ImportRecord;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ImportRecordPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $user instanceof \App\Models\Vendor;
    }

    public function view(Authenticatable $user, ImportRecord $record): bool
    {
        return $user instanceof \App\Models\Vendor
            && $record->vendor_id === $user->id;
    }

    public function create(Authenticatable $user): bool
    {
        return $user instanceof \App\Models\Vendor;
    }

    public function delete(Authenticatable $user, ImportRecord $record): bool
    {
        return $this->view($user, $record);
    }
}
