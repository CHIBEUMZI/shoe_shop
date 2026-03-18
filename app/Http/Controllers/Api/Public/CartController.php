<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\CartAddItemRequest;
use App\Http\Requests\CartUpdateItemRequest;
use App\Http\Resources\Public\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    private function getOrCreateCart(int $userId): Cart
    {
        return Cart::firstOrCreate(['user_id' => $userId]);
    }

    private function loadCart(Cart $cart): Cart
    {
        return $cart->load([
            'items.product',
            'items.variant',
            'items.variant.images',
        ]);
    }

    public function show(Request $request)
    {
        $cart = $this->getOrCreateCart($request->user()->id);
        $this->loadCart($cart);

        return new CartResource($cart);
    }

    public function addItem(CartAddItemRequest $request)
    {
        $payload = $request->validated();
        $qty = (int)($payload['quantity'] ?? 1);

        $cart = $this->getOrCreateCart($request->user()->id);

        DB::transaction(function () use ($cart, $payload, $qty) {
            $query = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('product_id', $payload['product_id']);

            if (!empty($payload['product_variant_id'])) {
                $query->where('product_variant_id', $payload['product_variant_id']);
            } else {
                $query->whereNull('product_variant_id');
            }

            $item = $query->lockForUpdate()->first();

            if ($item) {
                $item->quantity += $qty;
                $item->save();
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $payload['product_id'],
                    'product_variant_id' => $payload['product_variant_id'] ?? null,
                    'quantity' => $qty,
                ]);
            }
        });

        $this->loadCart($cart);

        return (new CartResource($cart))
            ->additional(['message' => 'Added to cart']);
    }

    public function updateItem(CartUpdateItemRequest $request, CartItem $item)
    {
        $cart = $this->getOrCreateCart($request->user()->id);
        abort_if($item->cart_id !== $cart->id, 403, 'Forbidden');

        $item->update([
            'quantity' => (int)$request->validated()['quantity'],
        ]);

        $this->loadCart($cart);

        return (new CartResource($cart))
            ->additional(['message' => 'Updated']);
    }

    public function removeItem(Request $request, CartItem $item)
    {
        $cart = $this->getOrCreateCart($request->user()->id);
        abort_if($item->cart_id !== $cart->id, 403, 'Forbidden');

        $item->delete();

        $this->loadCart($cart);

        return (new CartResource($cart))
            ->additional(['message' => 'Removed']);
    }

    public function clear(Request $request)
    {
        $cart = $this->getOrCreateCart($request->user()->id);
        $cart->items()->delete();

        $this->loadCart($cart);

        return (new CartResource($cart))
            ->additional(['message' => 'Cleared']);
    }
}