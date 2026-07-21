<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Both fields are no-ops for existing customers the instant this runs: a null
     * business_logo_path means every view falls back to the existing static
     * images/logo-circle.png (see ShopSetting::businessLogoUrl()), and 'maroon_gold' is the
     * config-defined theme whose CSS variable values are pixel-identical to today's hardcoded
     * Tailwind hex colors (see config/customer_themes.php). Nothing visually changes until an
     * admin actively uploads a logo or picks a different theme in Admin → Application Customization.
     */
    public function up(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->string('business_logo_path')->nullable()->after('business_mobile_number');
            $table->string('customer_theme', 30)->default('maroon_gold')->after('business_logo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->dropColumn(['business_logo_path', 'customer_theme']);
        });
    }
};
