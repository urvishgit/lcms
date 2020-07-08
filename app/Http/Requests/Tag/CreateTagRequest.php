<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;

class CreateTagRequest extends FormRequest
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
            'title' => 'required|min:3|max:100|unique:tags,title',
            'description' => 'required|min:3|max:500',
            'slug' => 'required|unique:articles,slug',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'A title is required.',
            'title.unique' => 'A title is need to be unique.',
            'description.required' => 'A description is required.',
            'slug.required' => 'A slug is required.',
        ];
    }
}
