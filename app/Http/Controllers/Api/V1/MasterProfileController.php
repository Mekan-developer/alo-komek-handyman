<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MasterProfileResource;
use Illuminate\Http\Request;

class MasterProfileController extends Controller
{
    public function show(Request $request): MasterProfileResource
    {
        return new MasterProfileResource(
            $request->user()->load('city', 'categories')
        );
    }
}
