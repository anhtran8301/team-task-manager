<?php

namespace App\Enums;

/** Stable application codes: general 10xxx, validation 11xxx, auth 12xxx, authorization 13xxx, tasks 14xxx. */
enum InternalCodeEnum: int
{
    case GET_DATA_SUCCESSFUL = 10000;
    case CSRF_COOKIE_CREATED = 10001;
    case NOT_FOUND = 10004;
    case METHOD_NOT_ALLOWED = 10005;
    case TOO_MANY_REQUESTS = 10029;
    case INTERNAL_SERVER_ERROR = 10500;
    case VALIDATION_FAILED = 11000;
    case UNAUTHORIZED = 12000;
    case LOGIN_SUCCESSFUL = 12001;
    case LOGOUT_SUCCESSFUL = 12002;
    case CSRF_TOKEN_MISMATCH = 12019;
    case FORBIDDEN = 13000;
    case TASK_CREATED = 14001;
    case TASK_UPDATED = 14002;
    case TASK_DELETED = 14003;

    /** Resolve the English message from the single translation catalog. */
    public function message(): string
    {
        return __('internal_code_message.'.$this->value);
    }

    /** @return array<int, string> Internal code keyed message. */
    public function getCodeMessageFormat(): array
    {
        return [$this->value => $this->message()];
    }
}
