<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
        if( auth()->user()->isadmin() == 'administrator' ) {
            return [
                'name' => 'required|min:3|max:100',
                'email' => 'bail|required|email|min:5|max:100|unique:users,email,'. $this->user->id,
                'password' => 'nullable|string|min:8|confirmed',
                'password_confirmation'=> 'nullable|string|min:8',
                'role' => 'required',
            ];
        } else {
            return [
            'name' => 'required|min:3|max:100',
            'email' => 'bail|required|email|min:5|max:100|unique:users,email,'. $this->user->id,
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation'=> 'required|string|min:8',
            'role' => 'required',
        ];
        }
    }
    public function messages()
    {
        return [
            'name.required' => 'A name is required.',
            'email.required' => 'A email is required.',
            'email.unique' => 'Email is already in used.',
            'email.email' => 'Please enter valid email address.',
            'password.required' => 'A password is required.',
            'password_confirmation.required' => 'A confirm password is required.',
            'role.required' => 'A role is required.',
        ];
    }
}
