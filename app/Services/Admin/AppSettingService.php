<?php
namespace App\Services\Admin;

use App\Models\AppSetting;
use App\Repositories\Admin\Interfaces\AppSettingInterfaces;
use Illuminate\Database\Eloquent\Collection;

class AppSettingService
{
    public function __construct(
        private AppSettingInterfaces $app
    ) {}

    public function getAll(): Collection
    {
        return $this->app->all();
    }
    
    public function getAllGroups(): array
    {
        return $this->app->getAllGroups();
    }
    
    public function getSettingsByGroup(string $group): Collection
    {
        return $this->app->getSettingsByGroup($group);
    }
    
    public function getSettingsGroupedByGroup(): array
    {
        $settings = $this->app->all();
        $grouped = [];
        
        foreach ($settings as $setting) {
            if (!isset($grouped[$setting->group])) {
                $grouped[$setting->group] = [];
            }
            $grouped[$setting->group][] = $setting;
        }
        
        return $grouped;
    }

    public function updateGroupSettings(string $group, array $settings): void
    {
        foreach ($settings as $key => $value) {
            $setting = AppSetting::where('group', $group)
                ->where('key', $key)
                ->first();
            
            if ($setting) {
                $setting->update(['value' => $value]);
            }
        }
    }
}