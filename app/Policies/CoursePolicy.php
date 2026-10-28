<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function create(User $u): bool
    {
        return $u->isTeacher() || $u->isAdmin();
    }

    public function update(User $u, Course $c): bool
    {
        return $u->isAdmin() || ($u->isTeacher() && $c->author_id === $u->id);
    }

    public function delete(User $u, Course $c): bool
    {
        return $this->update($u, $c);
    }

    public function publish(User $u, Course $c): bool
    {
        return $this->update($u, $c);
    }

    public function view(User $u, Course $c): bool
    {
        return $c->isPublished() || $this->update($u, $c);
    }
}
