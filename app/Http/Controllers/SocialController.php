<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SocialController extends Controller
{
    /**
     * Receives a JPEG (already converted client-side) for a social post,
     * stores it under public/images/social, and returns its public URL.
     * Protected by a shared secret (X-Upload-Secret) — called server-side
     * by the Mission Control approvals proxy, never from the browser directly.
     */
    public function uploadImage(Request $request)
    {
        $secret = config('services.social.upload_secret');
        if (!$secret || !hash_equals($secret, (string) $request->header('X-Upload-Secret'))) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $request->validate([
            'image' => 'required|file|mimes:jpeg,jpg|max:8192', // ≤8MB JPEG
        ]);

        $dir = public_path('images/social');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $name = Str::uuid()->toString() . '.jpg';
        $request->file('image')->move($dir, $name);

        return response()->json([
            'url' => 'https://api.tamratdates.com/images/social/' . $name,
        ]);
    }
}
