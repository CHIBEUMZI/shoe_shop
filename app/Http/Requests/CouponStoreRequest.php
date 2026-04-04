<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:coupons,code',
                'regex:/^[A-Z0-9]+$/i',
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => ['required', Rule::in(['percentage', 'fixed'])],
            'value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
            'applicable_to' => [
                'required',
                Rule::in(['all', 'specific_products', 'specific_categories', 'specific_brands']),
            ],
            'applicable_ids' => 'nullable|array',
            'applicable_ids.*' => 'integer',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Vui lòng nhập mã giảm giá.',
            'code.unique' => 'Mã giảm giá đã tồn tại.',
            'code.regex' => 'Mã giảm giá chỉ chứa chữ cái và số.',
            'name.required' => 'Vui lòng nhập tên mã giảm giá.',
            'type.required' => 'Vui lòng chọn loại giảm giá.',
            'type.in' => 'Loại giảm giá không hợp lệ.',
            'value.required' => 'Vui lòng nhập giá trị giảm giá.',
            'value.min' => 'Giá trị giảm giá phải lớn hơn 0.',
            'expires_at.after_or_equal' => 'Ngày hết hạn phải sau ngày bắt đầu.',
        ];
    }
}
