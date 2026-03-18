<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // Nếu bạn muốn lấy luôn cả cha lẫn con dạng tree:
        $q = Category::query()
            ->select(['id','parent_id','name','slug','sort_order','status'])
            ->where('status', 1)
            ->whereNull('parent_id')
            ->with(['children' => function ($qq) {
                $qq->select(['id','parent_id','name','slug','sort_order','status'])
                   ->where('status', 1)
                   ->orderBy('sort_order')
                   ->orderBy('id');
            }])
            ->orderBy('sort_order')
            ->orderBy('id');
        if ($request->boolean('with_children', true) === false) {
            $q->without('children');
        }
        return response()->json([
            'data' => $q->get()
        ]);
    }

    public function show(string $slug)
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => $category]);
    }
}