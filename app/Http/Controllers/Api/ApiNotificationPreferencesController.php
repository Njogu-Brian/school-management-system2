<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class ApiNotificationPreferencesController extends Controller
{
    private function keyForUser(int $userId): string
    {
        return "user_notification_preferences_{$userId}";
    }

    private function defaults(): array
    {
        return [
            'push_enabled' => true,
            'email_enabled' => true,
            'sms_enabled' => false,
            'attendance_alerts' => true,
            'fee_reminders' => true,
            'announcements' => true,
        ];
    }

    public function show(Request $request)
    {
        $user = $request->user();
        $stored = Setting::getJson($this->keyForUser((int) $user->id), []);
        $prefs = array_merge($this->defaults(), is_array($stored) ? $stored : []);

        return response()->json([
            'success' => true,
            'data' => $prefs,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'push_enabled' => ['required', 'boolean'],
            'email_enabled' => ['required', 'boolean'],
            'sms_enabled' => ['required', 'boolean'],
            'attendance_alerts' => ['required', 'boolean'],
            'fee_reminders' => ['required', 'boolean'],
            'announcements' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $prefs = array_merge($this->defaults(), $validated);
        Setting::setJson($this->keyForUser((int) $user->id), $prefs);

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences saved.',
            'data' => $prefs,
        ]);
    }
}
