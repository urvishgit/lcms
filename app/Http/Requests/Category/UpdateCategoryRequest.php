<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'bail|required|min:3|max:100|unique:categories,title,'. $this->category->id,
            'type' => 'required',
            'description' => 'required|min:3|max:500',
            'slug' => 'required|unique:articles,slug,'. $this->category->id,
        ];
    }
    public function messages()
    {
        return [
            'title.required' => 'A title is required.',
            'title.unique' => 'A title is need to be unique.',
            'type.required' => 'A type is required.',
            'description.required' => 'A description is required.',
            'slug.required' => 'A slug is required.',
        ];
    }
}
