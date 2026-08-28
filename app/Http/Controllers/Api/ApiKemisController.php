<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\KemisProfile;
use Illuminate\Http\JsonResponse;

class ApiKemisController extends Controller
{
    public function options(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => KemisProfile::optionsForApi(),
        ]);
    }
}
