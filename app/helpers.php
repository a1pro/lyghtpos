<?php
use App\Models\Page;
use App\Models\Representation;
use App\Models\RepresentationReviewLog;
use App\Models\User;
use App\Models\Notification;
use App\Models\GovernanceLayer;
use App\Models\UserGovernanceLayer;




if (!function_exists('activeSegment')) {
    function activeSegment($name, $segment = 2, $class = 'active')
    {
        return request()->segment($segment) == $name ? $class : '';
    }
}



if(!function_exists('representation_status')){
    function representation_status(){
        return Representation::where('user_id', auth()->id())->value('status');
    }
}

if(!function_exists('notifications')){
    function notifications()
    {
        return Notification::where('user_id',auth()->id())->value('message','created_at');
    }
}
?>