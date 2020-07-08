<?php

namespace App\Http\Requests\Module;

use Illuminate\Foundation\Http\FormRequest;

class UpdateModuleRequest extends FormRequest
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
            'title' => 'required|min:3|max:100|unique:modules,title,'. $this->module->id,
            'display_name' => 'required|min:3|max:100',
            'description' => 'required|min:10',
            'icon' => 'required',
            'route' => 'required',
            'trash' => 'required',
            'trash_route' => 'required_if:trash,1',
            'is_administrator_module' => 'required',
            'slug' => 'required|unique:modules,slug,'. $this->module->id,
            'published_at' => 'required',
        ];
    }
    public function messages()
    {
        return [
            'title.required' => 'A title is required.',
            'title.unique' => 'A title is need to be unique.',
            'display_name.required' => 'A display name is required.',
            'description.required'  => 'A description is required.',
            'icon.required' => 'A icon is required.',
            'route.required' => 'A route is required.',
            'trash.required' => 'A trash is required.',
            'trash_route.required_if' => 'A trash route is required.',
            'is_administrator_module.required' => 'A is administrator module is required.',
            'slug.required' => 'A slug is required.',
            'published_at.required' => 'A published date is required.',
        ];
    }
}
