<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrandStoreRequest;
use App\Http\Requests\BrandUpdateRequest;
use App\Http\Resources\Admin\BrandResource;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);

        $query = Brand::query()
            ->when($request->search, fn ($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('slug', 'like', '%' . $request->search . '%')
            )
            ->when(isset($request->status) && $request->status !== '', fn ($q) =>
                $q->where('status', (int) $request->status)
            )
            ->latest();

        return BrandResource::collection($query->paginate($perPage));
    }

    public function show(Brand $brand)
    {
        return new BrandResource($brand);
    }

    public function store(BrandStoreRequest $request)
    {
        $data = $request->validated();
        $brand = Brand::create($data);

        return new BrandResource($brand);
    }

    public function update(BrandUpdateRequest $request, Brand $brand)
    {
        $data = $request->validated();
        $brand->update($data);

        return new BrandResource($brand);
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();
        return response()->json(['message' => 'Deleted']);
    }
}