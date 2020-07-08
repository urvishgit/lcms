<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;

class CreateTeamRequest extends FormRequest
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
            'title' => 'required|min:3|max:100|unique:team,title',
            'slug' => 'required|unique:team,slug',
            'category_id' => 'required',
            'description' => 'required|min:10',
            'content' => 'required|min:10',
            'job_title' => 'required|min:3|max:100',
            'email' => 'required|email',
            'image' => 'required|image',
            'published_at' => 'required',
        ];
    }
    public function messages()
    {
        return [
            'title.required' => 'A name is required.',
            'title.unique' => 'A name is need to be unique.',
            'category_id.required' => 'A category is required.',
            'description.required' => 'A description is required.',
            'content.required' => 'A content is required.',
            'job_title.required' => 'A job title is required.',
            'email.unique' => 'Email is already in used.',
            'email.email' => 'Please enter valid email address.',
            'slug.required' => 'A slug is required.',
            'image.required' => 'A image is required.',
            'published_at.required' => 'A published date is required.',
        ];
    }  
}
