<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    /**
     * Получить список всех категорий с пагинацией и количеством привязанных курсов.
     *
     * @return LengthAwarePaginator<Category>
     */
    public function getPaginatedCategories(int $perPage = 15): LengthAwarePaginator
    {
        return Category::withCount('courses')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Получить все категории без пагинации.
     *
     * @return Collection<int, Category>
     */
    public function getAllCategories(): Collection
    {
        return Category::withCount('courses')
            ->orderBy('name')
            ->get();
    }

    /**
     * Получить детальную информацию о категории и её курсах.
     */
    public function getCategoryDetails(Category $category): Category
    {
        return $category->load(['courses' => function ($query) {
            $query->where('status', 'published');
        }]);
    }

    /**
     * Создать новую категорию.
     *
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data): Category
    {
        return Category::create($data);
    }

    /**
     * Обновить существующую категорию.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateCategory(Category $category, array $data): Category
    {
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
