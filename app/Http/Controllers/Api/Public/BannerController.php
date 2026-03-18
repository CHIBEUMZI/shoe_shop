<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\BannerResource;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index(Request $request)
    {
        $position = $request->string('position')->toString();

        $query = Banner::query()
            ->active()
            ->orderBy('sort_order')
            ->latest('id');

        if ($position !== '') {
            $query->where('position', $position);
        }

        return BannerResource::collection($query->get());
    }

    public function showByPosition(string $position)
    {
        $banner = Banner::query()
            ->active()
            ->where('position', $position)
            ->orderBy('sort_order')
            ->latest('id')
            ->first();

        if (!$banner) {
            return response()->json([
                'message' => 'Không tìm thấy banner.',
                'data' => null,
            ], 404);
        }

        return new BannerResource($banner);
    }
}