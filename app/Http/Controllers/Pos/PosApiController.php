<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Responses\PosApiResponse;

abstract class PosApiController extends Controller
{
    protected function success(
        mixed $data = null,
        string $message = '',
        int $status = 200
    ) {
        return PosApiResponse::success($data, $message, $status);
    }

    protected function error(
        string $code,
        string $message,
        mixed $details = null,
        int $status = 400
    ) {
        return PosApiResponse::error($code, $message, $details, $status);
    }
}
