<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CategoryService
{
    /**
     * Получить все категории с количеством курсов.
     *
     * @return Collection<int, Category>
     */
    public function getAllCategories(): Collection
    {
        return Category::withCount('courses')->get();
    }

    /**
     * Получить категории с пагинацией.
     */
    public function getPaginatedCategories(int $perPage = 15): LengthAwarePaginator
    {
        return Category::withCount('courses')->latest()->paginate($perPage);
    }

    /**
     * Создать новую категорию.
     *
     * @param  array{name: string, description?: string|null}  $data
     */
    public function createCategory(array $data): Category
    {
        return Category::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * Обновить существующую категорию.
     *
     * @param  array{name?: string, description?: string|null}  $data
     */
    public function updateCategory(Category $category, array $data): Category
    {
        if (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return $category->fresh();
    }

    /**
     * Удалить категорию.
     */
    public function deleteCategory(Category $category): void
    {
        $category->delete();
    }
}
