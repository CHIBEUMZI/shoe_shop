<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Http\Resources\Admin\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);

        $query = Category::query()
            ->with(['parent'])
            ->withCount(['children', 'products'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->input('search');
                $q->where(function ($qq) use ($s) {
                    $qq->where('name', 'like', "%{$s}%")
                       ->orWhere('slug', 'like', "%{$s}%");
                });
            })
            ->when($request->filled('status'), fn ($q) =>
                $q->where('status', (int) $request->input('status'))
            )
            ->when($request->filled('parent_id'), fn ($q) =>
                $q->where('parent_id', $request->integer('parent_id'))
            )
            // ✅ sort theo sort_order trước
            ->orderBy('sort_order')
            ->orderByDesc('id');

        return CategoryResource::collection($query->paginate($perPage));
    }

    public function show(Category $category)
    {
        $category->load(['parent'])
            ->loadCount(['children', 'products']);

        return new CategoryResource($category);
    }

    public function store(CategoryStoreRequest $request)
    {
        $data = $request->validated();

        $category = Category::create([
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? 1,
        ]);

        $category->load(['parent'])
            ->loadCount(['children', 'products']);

        return new CategoryResource($category);
    }

    public function update(CategoryUpdateRequest $request, Category $category)
    {
        $data = $request->validated();

        $category->update([
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
            'status' => $data['status'],
        ]);

        $category->load(['parent'])
            ->loadCount(['children', 'products']);

        return new CategoryResource($category);
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
