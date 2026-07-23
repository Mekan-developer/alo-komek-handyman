<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCategoryContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title_ru' => ['required', 'string', 'max:255'],
            'title_tk' => ['required', 'string', 'max:255'],
            'description_ru' => ['nullable', 'string', 'max:5000'],
            'description_tk' => ['nullable', 'string', 'max:5000'],
            'price' => ['nullable', 'string', 'max:255'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:10240'],
            'keep_ids' => ['nullable', 'array'],
            'keep_ids.*' => ['integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $hasNewImages = $this->hasFile('images');
            /** @var Category $category */
            $category = $this->route('category');
            $keepIds = $this->input('keep_ids', []);
            $hasKeptImages = count($keepIds) > 0 && $category->content?->images()->whereIn('id', $keepIds)->exists();

            if (! $hasNewImages && ! $hasKeptImages) {
                $v->errors()->add('images', __('categories.content_images_required'));
            }
        });
    }
}
