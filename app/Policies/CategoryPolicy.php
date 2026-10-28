<?php

namespace App\Policies;

use App\Models\User;

class CategoryPolicy
{
    public function before(User $u): ?bool
    {
        return $u->isAdmin() ? true : null;
    }

    public function create(User $u): bool
    {
        return $u->hasPermission('categories.manage');
    }

    public function update(User $u): bool
    {
        return $this->create($u);
    }

    public function delete(User $u): bool
    {
        return $this->create($u);
    }
}
