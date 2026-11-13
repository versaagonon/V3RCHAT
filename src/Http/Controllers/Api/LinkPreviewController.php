<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Services\LinkPreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class LinkPreviewController extends Controller
{
    public function show(Request $request, LinkPreviewService $linkPreviewService): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'url', 'max:2048'],
        ]);

        $url = $validated['url'];

        try {
            $linkPreviewService->assertPublicUrl($url);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'url' => [__('chatify::chatify.errors.only_http_https_urls')],
            ]);
        }

        $cacheKey = 'chatify:link_preview:'.md5($url);
        $preview = Cache::remember($cacheKey, 600, fn () => $linkPreviewService->fetch($url));

        return response()->json(['data' => $preview]);
    }
}
