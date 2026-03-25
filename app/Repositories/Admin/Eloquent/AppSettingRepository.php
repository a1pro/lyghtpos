<?php
namespace App\Repositories\Admin\Eloquent;

use App\Models\AppSetting;
use App\Repositories\Admin\Interfaces\AppSettingInterfaces;
use Illuminate\Database\Eloquent\Collection;

class AppSettingRepository implements AppSettingInterfaces
{
    public function all(): Collection
    {
        return AppSetting::orderBy('group')
            ->orderBy('name')
            ->get();
    }
    
    public function getAllGroups(): array
    {
        return AppSetting::select('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->toArray();
    }
    
    public function getSettingsByGroup(string $group): Collection
    {
        return AppSetting::where('group', $group)
            ->orderBy('name')
            ->get();
    }
}