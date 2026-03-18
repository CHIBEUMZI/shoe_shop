<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBannerRequest;
use App\Http\Requests\UpdateBannerRequest;
use App\Http\Resources\Public\BannerResource;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);
        $search = trim((string) $request->get('search', ''));
        $position = trim((string) $request->get('position', ''));

        $query = Banner::query()->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($position !== '') {
            $query->where('position', $position);
        }

        return BannerResource::collection($query->paginate($perPage));
    }

    public function store(StoreBannerRequest $request)
    {
        $banner = Banner::create($request->validated());

        return response()->json([
            'message' => 'Tạo banner thành công.',
            'data' => new BannerResource($banner),
        ], 201);
    }

    public function show(Banner $banner)
    {
        return new BannerResource($banner);
    }

    public function update(UpdateBannerRequest $request, Banner $banner)
    {
        $banner->update($request->validated());

        return response()->json([
            'message' => 'Cập nhật banner thành công.',
            'data' => new BannerResource($banner),
        ]);
    }

    public function destroy(Banner $banner)
    {
        $banner->delete();

        return response()->json([
            'message' => 'Xoá banner thành công.',
        ]);
    }
}