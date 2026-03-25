<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppSetting;
use App\Services\Admin\AppSettingService;

class AppSettingController extends Controller
{
    public function __construct(
        private AppSettingService $service
    ) {}

    public function index()
    {
        // Get all groups with their settings
        $groups = $this->service->getAllGroups();
        $settingsByGroup = $this->service->getSettingsGroupedByGroup();
        
        // Get first group as active if no group selected
        $activeGroup = request()->get('group', !empty($groups) ? $groups[0] : null);
        
        return view('admin.app-setting.index', compact('settingsByGroup', 'groups', 'activeGroup'));
    }

    public function update(Request $request, $group)
    {
        try {
            $settings = $request->except('_token', '_method');
            
            foreach ($settings as $key => $value) {
                // Find the setting by group and key
                $setting = AppSetting::where('group', $group)
                    ->where('key', $key)
                    ->first();
                
                if ($setting) {
                    $setting->update(['value' => $value]);
                }
            }
            
            return redirect()->route('admin.app-settings.index', ['group' => $group])
                ->with('success', 'Settings updated successfully');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }
    
    public function getGroupSettings($group)
    {
        $settings = AppSetting::where('group', $group)->get();
        return response()->json($settings);
    }
}