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

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
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
     * Approve a review
     */
    public function approve(Review $review)
    {
        $review->update(['status' => 'approved']);

        return response()->json([
            'message' => 'Review approved successfully',
            'data' => new ReviewResource($review->fresh(['user', 'product'])),
        ], 200);
    }

    /**
     * Reject a review
     */
    public function reject(Review $review)
    {
        $review->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Review rejected successfully',
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

    // /**
    //  * Bulk action on reviews
    //  */
    // public function bulkAction(Request $request)
    // {
    //     $request->validate([
    //         'ids' => 'required|array',
    //         'ids.*' => 'integer|exists:reviews,id',
    //         'action' => 'required|in:approve,reject,delete',
    //     ]);

    //     $count = 0;

    //     foreach ($request->ids as $id) {
    //         $review = Review::find($id);
    //         if (!$review) continue;

    //         switch ($request->action) {
    //             case 'approve':
    //                 $review->update(['status' => 'approved']);
    //                 break;
    //             case 'reject':
    //                 $review->update(['status' => 'rejected']);
    //                 break;
    //             case 'delete':
    //                 $review->delete();
    //                 break;
    //         }
    //         $count++;
    //     }

    //     return response()->json([
    //         'message' => "Successfully performed {$request->action} on {$count} review(s)",
    //         'count' => $count,
    //     ]);
    // }

    /**
     * Get review statistics
     */
    public function stats()
    {
        return response()->json([
            'total' => Review::count(),
            'pending' => Review::where('status', 'pending')->count(),
            'approved' => Review::where('status', 'approved')->count(),
            'rejected' => Review::where('status', 'rejected')->count(),
            'by_rating' => [
                '5' => Review::where('rating', 5)->where('status', 'approved')->count(),
                '4' => Review::where('rating', 4)->where('status', 'approved')->count(),
                '3' => Review::where('rating', 3)->where('status', 'approved')->count(),
                '2' => Review::where('rating', 2)->where('status', 'approved')->count(),
                '1' => Review::where('rating', 1)->where('status', 'approved')->count(),
            ],
        ]);
    }
}
