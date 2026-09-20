<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LoginCaptcha
{
    /** @return array{id: string, image: string, expires_in: int} */
    public function create(): array
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $answer = '';
        for ($i = 0; $i < 6; $i++) {
            $answer .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        $id = (string) Str::uuid();
        $image = imagecreatetruecolor(132, 40);
        imagefill($image, 0, 0, imagecolorallocate($image, 245, 247, 250));
        for ($i = 0; $i < 8; $i++) {
            imageline($image, random_int(0, 131), random_int(0, 39), random_int(0, 131), random_int(0, 39), imagecolorallocate($image, 180, 190, 200));
        }
        for ($i = 0; $i < 6; $i++) {
            imagestring($image, 5, 12 + $i * 19, random_int(8, 17), $answer[$i], imagecolorallocate($image, 90, 25, 35));
        }
        $scaled = imagescale($image, 264, 80);
        ob_start();
        imagepng($scaled);
        $png = ob_get_clean();
        Cache::put('login-captcha:'.$id, hash('sha256', $answer), now()->addMinutes(5));

        return ['id' => $id, 'image' => 'data:image/png;base64,'.base64_encode($png), 'expires_in' => 300];
    }

    public function verify(string $id, string $answer): bool
    {
        return (bool) Cache::lock('login-captcha-lock:'.$id, 10)->get(function () use ($id, $answer): bool {
            $expected = Cache::pull('login-captcha:'.$id);

            return is_string($expected) && hash_equals($expected, hash('sha256', strtoupper(trim($answer))));
        });
    }
}
