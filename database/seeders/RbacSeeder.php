<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    /**
     * Запустить сидирование ролей и прав доступа.
     */
    public function run(): void
    {
        // Создаем базовые роли
        $adminRole = Role::firstOrCreate(
            ['name' => UserRole::Admin->value],
            ['description' => 'Администратор платформы с полным доступом']
        );

        $teacherRole = Role::firstOrCreate(
            ['name' => UserRole::Teacher->value],
            ['description' => 'Преподаватель: создание и управление своими курсами']
        );

        $studentRole = Role::firstOrCreate(
            ['name' => UserRole::Student->value],
            ['description' => 'Студент: прохождение курсов и получение сертификатов']
        );

        // Список базовых прав доступа
        $permissions = [
            'users.view' => 'Просмотр списка пользователей',
            'users.manage' => 'Управление пользователями (роли, блокировка)',
            'courses.view' => 'Просмотр курсов',
            'courses.create' => 'Создание курсов',
            'courses.edit' => 'Редактирование курсов',
            'courses.delete' => 'Удаление курсов',
            'courses.publish' => 'Публикация курсов',
            'courses.purchase' => 'Покупка курсов',
            'lessons.view' => 'Просмотр уроков',
            'lessons.complete' => 'Отметка урока завершенным',
            'certificates.view' => 'Просмотр и скачивание сертификатов',
            'categories.manage' => 'Управление категориями',
        ];

        $permissionModels = [];
        foreach ($permissions as $name => $description) {
            $permissionModels[$name] = Permission::firstOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }

        // Назначаем права ролям
        // Администратор получает все права
        $adminRole->permissions()->sync(array_values(array_map(fn ($p) => $p->id, $permissionModels)));

        // Преподаватель
        $teacherRole->permissions()->sync([
            $permissionModels['courses.view']->id,
            $permissionModels['courses.create']->id,
            $permissionModels['courses.edit']->id,
            $permissionModels['courses.delete']->id,
            $permissionModels['courses.publish']->id,
            $permissionModels['lessons.view']->id,
            $permissionModels['certificates.view']->id,
        ]);

        // Студент
        $studentRole->permissions()->sync([
            $permissionModels['courses.view']->id,
            $permissionModels['courses.purchase']->id,
            $permissionModels['lessons.view']->id,
            $permissionModels['lessons.complete']->id,
            $permissionModels['certificates.view']->id,
        ]);
    }
}
