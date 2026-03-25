<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AppSetting;

class AppSettingSeeder extends Seeder
{
    public function run(): void
    {
        // Cyclos Settings
        AppSetting::create([
            'group' => 'cyclos',
            'name' => 'Cyclos Base Url',
            'key' => 'CYCLOS_BASE_URL',
            'value' => 'https://communyti.whelastic.net',
           
        ]);
        
        AppSetting::create([
            'group' => 'cyclos',
            'name' => 'Secret Key',
            'key' => 'CYCLOS_API_USERNAME',
            'value' => 'williammorris',
           
        ]);
        
        AppSetting::create([
            'group' => 'cyclos',
            'name' => 'Cyclos Api Password',
            'key' => 'CYCLOS_API_PASSWORD',
            'value' => 'forGod11099',
            
        ]);
        
        // PayPal Settings
        AppSetting::create([
            'group' => 'cyclos',
            'name' => 'Cyclos Admin User ID',
            'key' => 'CYCLOS_ADMIN_USER_ID',
            'value' => '7157739016491622572',
           
        ]);
        
        AppSetting::create([
            'group' => 'cyclos',
            'name' => 'Cyclos Payment Type ID',
            'key' => 'CYCLOS_PAYMENT_TYPE_ID',
            'value' => '7157739016488738988',
            
        ]);
        
        AppSetting::create([
            'group' => 'cyclos',
            'name' => 'Cyclos Currency Name',
            'key' => 'CYCLOS_CURRENCY_NAME',
            'value' => 'TradeTokens',
            
        ]);
        
        // Stripe Settings (example)
        AppSetting::create([
            'group' => 'cyclos',
            'name' => 'Cyclos Currency Symbol',
            'key' => 'CYCLOS_CURRENCY_SYMBOL',
            'value' => 'TT',
            
        ]);
        
        AppSetting::create([
            'group' => 'authorize',
            'name' => 'Authorize Api Login Id',
            'key' => 'AUTHORIZE_API_LOGIN_ID',
            'value' => '3d3Kz5Kb',
            
        ]);

        AppSetting::create([
            'group' => 'authorize',
            'name' => 'Authorize Transaction Key',
            'key' => 'AUTHORIZE_TRANSACTION_KEY',
            'value' => '6fZh3E38Z3Lpu72s',
            
        ]);


        AppSetting::create([
            'group' => 'paypal',
            'name' => 'Paypal Cliend ID',
            'key' => 'PAYPAL_SANDBOX_CLIENT_ID',
            'value' => 'AYEY2Tyhd32Ku4A1iQhzVeip-KSSlrRdBzG7w8XOdStp2XpB5jRkQCa9D8J_b4O0-4AMnY9y3n9aso5J',
            
        ]);


        AppSetting::create([
            'group' => 'paypal',
            'name' => 'Paypal Secret Id',
            'key' => 'PAYPAL_SANDBOX_CLIENT_SECRET',
            'value' => 'ELZoQjHoO1YtKfhNM9C6NwuAGwUnltVTV0d6b_GsMtCILI6tmabe5xL0yUcbk7514h1U7yqLi-hliCml',
            
        ]);
        
    }
}