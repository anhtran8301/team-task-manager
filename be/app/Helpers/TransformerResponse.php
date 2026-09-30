<?php

namespace App\Helpers;

use App\Enums\InternalCodeEnum;
use Illuminate\Http\Response;

class TransformerResponse
{
    /**
     * Build the sole public API envelope, accepting models and paginators.
     *
     * @param  array<string, list<string>>  $errors  Validation errors keyed by field.
     */
    public function response(bool $isSuccess = true, mixed $data = [], int $code = Response::HTTP_OK,
        InternalCodeEnum $message = InternalCodeEnum::GET_DATA_SUCCESSFUL, array $errors = []): Response
    {
        $body = ['success' => $isSuccess, 'code' => $code, 'message' => $message->getCodeMessageFormat(), 'data' => $data];
        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        return new Response($body, $code);
    }
}
