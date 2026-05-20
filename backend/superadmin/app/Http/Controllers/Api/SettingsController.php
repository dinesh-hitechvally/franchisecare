<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $group = $request->get('group');

        $query = SystemSetting::query();

        if ($group) {
            $query->where('group', $group);
        }

        return response()->json($query->orderBy('group')->orderBy('key')->get());
    }

    public function show(string $key)
    {
        $setting = SystemSetting::where('key', $key)->first();

        if (!$setting) {
            return response()->json(['message' => 'Setting not found'], 404);
        }

        return response()->json($setting);
    }

    public function update(Request $request, string $key)
    {
        $request->validate([
            'value' => 'required',
        ]);

        $setting = SystemSetting::where('key', $key)->first();

        if (!$setting) {
            return response()->json(['message' => 'Setting not found'], 404);
        }

        $setting->update(['value' => $request->value]);

        return response()->json($setting);
    }

    public function updateBulk(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        foreach ($request->settings as $item) {
            SystemSetting::where('key', $item['key'])->update(['value' => $item['value']]);
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }

    public function getByGroup(string $group)
    {
        $settings = SystemSetting::where('group', $group)->get();

        return response()->json($settings);
    }

    public function groups()
    {
        $groups = SystemSetting::select('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group');

        return response()->json($groups);
    }
}
