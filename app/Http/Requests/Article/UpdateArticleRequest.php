<?php

namespace App\Http\Requests\Article;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArticleRequest extends FormRequest
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
            'title' => 'required|min:3|max:100|unique:articles,title,'. $this->article->id,
            'category_id' => 'required',
            'tags' => 'required|array|min:1',
            'description' => 'required|min:10',
            'content' => 'required|min:10',
            'slug' => 'required|unique:articles,slug,'. $this->article->id,
            'image' => 'required|image',
            'published_at' => 'required',
        ];
    }
    public function messages()
    {
        return [
            'title.required' => 'A title is required.',
            'title.unique' => 'A title is need to be unique.',
            'category_id.required' => 'A category is required.',
            'tags.required'  => 'A tag is required.',
            'description.required' => 'A description is required.',
            'content.required' => 'A content is required.',
            'slug.required' => 'A slug is required.',
            'image.required' => 'A image is required.',
            'published_at.required' => 'A published date is required.',
        ];
    }
}
