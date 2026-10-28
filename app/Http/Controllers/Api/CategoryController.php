<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Получить список всех категорий с пагинацией.
     */
    public function index(Request $request): JsonResponse
    {
        $categories = $this->categoryService->getPaginatedCategories(
            $request->integer('per_page', 15)
        );

        return response()->json($categories, Response::HTTP_OK);
    }

    /**
     * Создать новую категорию (только администратор).
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->createCategory($request->validated());

        return response()->json([
            'message' => 'Категория успешно создана',
            'category' => $category,
        ], Response::HTTP_CREATED);
    }

    /**
     * Получить информацию о конкретной категории.
     */
    public function show(Category $category): JsonResponse
    {
        $categoryDetails = $this->categoryService->getCategoryDetails($category);

        return response()->json([
            'category' => $categoryDetails,
        ], Response::HTTP_OK);
    }

    /**
     * Обновить категорию (только администратор).
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $updatedCategory = $this->categoryService->updateCategory($category, $request->validated());

        return response()->json([
            'message' => 'Категория успешно обновлена',
            'category' => $updatedCategory,
        ], Response::HTTP_OK);
    }

    /**
     * Удалить категорию (только администратор).
     */
    public function destroy(Category $category): JsonResponse
    {
        Gate::authorize('delete', $category);

        $this->categoryService->deleteCategory($category);

        return response()->json([
            'message' => 'Категория успешно удалена',
        ], Response::HTTP_OK);
    }
}
