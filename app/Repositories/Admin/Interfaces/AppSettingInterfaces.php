<?php
namespace App\Repositories\Admin\Interfaces;

use App\Models\AppSetting;
use Illuminate\Database\Eloquent\Collection;

interface AppSettingInterfaces
{
    public function all(): Collection;
    public function getAllGroups(): array;
    public function getSettingsByGroup(string $group): Collection;
   
}