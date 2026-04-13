<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewAdminController extends Controller
{
    /**
     * Get all reviews with filters
     */
    public function index(Request $request)
    {
        $query = Review::with(['user', 'product']);

        // Filter by has_reply
        if ($request->has('has_reply')) {
            $hasReply = filter_var($request->has_reply, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($hasReply === true) {
                $query->whereNotNull('admin_reply');
            } elseif ($hasReply === false) {
                $query->whereNull('admin_reply');
            }
        }

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Search by user name or comment
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhere('comment', 'like', "%{$search}%");
        }

        // Order
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $reviews = $query->paginate($request->get('per_page', 15));

        return ReviewResource::collection($reviews);
    }

    /**
     * Show a specific review
     */
    public function show(Review $review)
    {
        return new ReviewResource($review->load(['user', 'product']));
    }

    /**
     * Reply to a review
     */
    public function reply(Request $request, Review $review)
    {
        $request->validate([
            'admin_reply' => 'required|string|max:1000',
        ]);

        $review->update([
            'admin_reply' => $request->admin_reply,
            'replied_at' => now(),
        ]);

        return response()->json([
            'message' => 'Đã trả lời đánh giá thành công',
            'data' => new ReviewResource($review->fresh(['user', 'product'])),
        ], 200);
    }

    /**
     * Delete a review
     */
    public function delete(Review $review)
    {
        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully',
        ], 200);
    }

    /**
     * Get review statistics
     */
    public function stats()
    {
        return response()->json([
            'total' => Review::count(),
            'replied' => Review::whereNotNull('admin_reply')->count(),
            'unreplied' => Review::whereNull('admin_reply')->count(),
            'by_rating' => [
                '5' => Review::where('rating', 5)->count(),
                '4' => Review::where('rating', 4)->count(),
                '3' => Review::where('rating', 3)->count(),
                '2' => Review::where('rating', 2)->count(),
                '1' => Review::where('rating', 1)->count(),
            ],
        ]);
    }
}
