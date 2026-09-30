<?php

namespace App\Helpers;

use App\Enums\InternalCodeEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ApiExceptionRenderer
{
    /** Preserve framework HTTP semantics without exposing exception details. */
    public function render(Throwable $exception): Response
    {
        if ($exception instanceof HttpResponseException && $exception->getResponse() instanceof Response) {
            return $exception->getResponse();
        }

        $status = match (true) {
            $exception instanceof ValidationException => Response::HTTP_UNPROCESSABLE_ENTITY,
            $exception instanceof AuthenticationException => Response::HTTP_UNAUTHORIZED,
            $exception instanceof AuthorizationException => Response::HTTP_FORBIDDEN,
            $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };

        $message = match ($status) {
            Response::HTTP_UNAUTHORIZED => InternalCodeEnum::UNAUTHORIZED,
            Response::HTTP_FORBIDDEN => InternalCodeEnum::FORBIDDEN,
            Response::HTTP_NOT_FOUND => InternalCodeEnum::NOT_FOUND,
            Response::HTTP_METHOD_NOT_ALLOWED => InternalCodeEnum::METHOD_NOT_ALLOWED,
            419 => InternalCodeEnum::CSRF_TOKEN_MISMATCH, // Laravel's non-standard CSRF status.
            Response::HTTP_UNPROCESSABLE_ENTITY => InternalCodeEnum::VALIDATION_FAILED,
            Response::HTTP_TOO_MANY_REQUESTS => InternalCodeEnum::TOO_MANY_REQUESTS,
            default => InternalCodeEnum::INTERNAL_SERVER_ERROR,
        };

        $response = app(TransformerResponse::class)->response(false, [], $status, $message,
            $exception instanceof ValidationException ? $exception->errors() : []);

        if ($exception instanceof HttpExceptionInterface) {
            $response->headers->add($exception->getHeaders());
        }

        return $response;
    }
}
