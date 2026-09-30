<?php

namespace App\Helpers;

use App\Enums\InternalCodeEnum;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

abstract class CommonFormRequest extends FormRequest
{
    /** Routes check Policies; specialized requests authorize validated assignment after validation. */
    public function authorize(): bool
    {
        return true;
    }

    /** Stop validation with the same envelope as every other API response. */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(TransformerResponse::class)->response(
            false, [], Response::HTTP_UNPROCESSABLE_ENTITY, InternalCodeEnum::VALIDATION_FAILED, $validator->errors()->messages()
        ));
    }
}
