<?php

namespace App\Modules\V1\Auth\Requests;

use App\Helpers\CommonFormRequest;

class AuthLoginRequest extends CommonFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'not_regex:/[\r\n]/', 'max:'.config('task_manager.limits.email'),
            ],
            'password' => [
                'required',
                'string',
                'max:'.config('task_manager.limits.password'),
            ],
        ];
    }
}
