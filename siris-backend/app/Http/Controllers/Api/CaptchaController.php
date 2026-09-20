<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LoginCaptcha;
use Illuminate\Http\JsonResponse;

class CaptchaController extends Controller
{
    public function __invoke(LoginCaptcha $captcha): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $captcha->create()])
            ->header('Cache-Control', 'no-store, private');
    }
}
