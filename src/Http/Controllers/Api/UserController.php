<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Http\Resources\UserResource;
use Chatify\Support\ChatifyModels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserController extends Controller
{
    public function show(Request $request, int $user): JsonResponse
    {
        $viewer = $request->user();
        if ($viewer === null) {
            abort(401);
        }

        $model = ChatifyModels::userClass()::query()->findOrFail($user);

        return (new UserResource($model))->response();
    }
}
