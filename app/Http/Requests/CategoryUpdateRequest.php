<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('category')?->id;

        return [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                // không cho parent_id = chính nó
                Rule::notIn([$id]),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($id),
            ],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'integer', Rule::in([0, 1])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('sort_order') === false) {
            $this->merge(['sort_order' => 0]);
        }
    }
}
