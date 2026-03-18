<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'folder' => ['nullable', 'string', 'in:products,brands,categories,avatars,banners,uploads'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Vui lòng chọn file.',
            'file.image' => 'File phải là ảnh.',
            'file.mimes' => 'Ảnh phải có định dạng jpg, jpeg, png, webp hoặc gif.',
            'file.max' => 'Kích thước ảnh tối đa là 5MB.',
            'folder.in' => 'Folder upload không hợp lệ.',
        ];
    }
}