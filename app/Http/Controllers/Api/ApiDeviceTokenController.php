<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiDeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string|max:512',
            'platform' => 'nullable|string|max:32',
        ]);

        $user = $request->user();
        $now = now();
        $token = $request->input('token');

        $existing = DB::table('user_device_tokens')
            ->where('user_id', $user->id)
            ->where('token', $token)
            ->first();

        if ($existing) {
            DB::table('user_device_tokens')
                ->where('id', $existing->id)
                ->update([
                    'platform' => $request->input('platform'),
                    'updated_at' => $now,
                ]);
        } else {
            DB::table('user_device_tokens')->insert([
                'user_id' => $user->id,
                'token' => $token,
                'platform' => $request->input('platform'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Device token saved.',
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'token' => 'required|string|max:512',
        ]);

        DB::table('user_device_tokens')
            ->where('user_id', $request->user()->id)
            ->where('token', $request->input('token'))
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device token removed.',
        ]);
    }
}
