<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Get reviews for a product
     */
    public function index(Product $product)
    {
        $reviews = $product->reviews()
            ->with('user')
            ->latest()
            ->paginate(10);

        return ReviewResource::collection($reviews);
    }

    /**
     * Store a new review
     */
    public function store(ReviewRequest $request)
    {
        // Check if user has purchased this product
        $hasPurchased = auth()->user()->orders()
            ->whereHas('items', function ($query) use ($request) {
                $query->where('product_id', $request->product_id);
            })
            ->exists();

        if (!$hasPurchased) {
            return response()->json([
                'message' => 'Bạn cần mua sản phẩm này trước khi viết đánh giá',
            ], 403);
        }

        // Check if user already reviewed this product
        $existingReview = Review::where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'Bạn đã viết đánh giá cho sản phẩm này',
            ], 422);
        }

        $review = Review::create([
            'user_id' => auth()->id(),
            'product_id' => $request->product_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'verified_purchase' => true,
        ]);

        return response()->json(new ReviewResource($review->load('user')), 201);
    }

    /**
     * Show a specific review
     */
    public function show(Review $review)
    {
        return response()->json(new ReviewResource($review->load('user')));
    }

    /**
     * Update a review
     */
    public function update(ReviewRequest $request, Review $review)
    {
        // Authorization check
        if ($review->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $review->update($request->validated());

        return response()->json(new ReviewResource($review));
    }

    /**
     * Delete a review
     */
    public function destroy(Review $review)
    {
        // Authorization check
        if ($review->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully',
        ]);
    }

    /**
     * Get user's own reviews
     */
    public function myReviews()
    {
        $reviews = auth()->user()->reviews()
            ->with('product')
            ->latest()
            ->paginate(10);

        return ReviewResource::collection($reviews);
    }

    /**
     * Check if user has purchased a product
     */
    public function checkPurchase(Product $product)
    {
        $hasPurchased = auth()->user()->orders()
            ->whereHas('items', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->exists();

        return response()->json([
            'has_purchased' => $hasPurchased,
        ]);
    }

    /**
     * Get product statistics
     */
    public function productStats(Product $product)
    {
        $reviews = $product->reviews();
        $totalReviews = $reviews->count();
        
        if ($totalReviews === 0) {
            return response()->json([
                'total_reviews' => 0,
                'average_rating' => 0,
                'rating_distribution' => [
                    '5' => 0,
                    '4' => 0,
                    '3' => 0,
                    '2' => 0,
                    '1' => 0,
                ],
            ]);
        }

        $averageRating = round($reviews->avg('rating'), 1);
        $ratingDistribution = [
            '5' => $reviews->where('rating', 5)->count(),
            '4' => $reviews->where('rating', 4)->count(),
            '3' => $reviews->where('rating', 3)->count(),
            '2' => $reviews->where('rating', 2)->count(),
            '1' => $reviews->where('rating', 1)->count(),
        ];

        return response()->json([
            'total_reviews' => $totalReviews,
            'average_rating' => $averageRating,
            'rating_distribution' => $ratingDistribution,
        ]);
    }
}
