<?php

namespace Database\Seeders;

use App\Models\ExcelSetting;
use Illuminate\Database\Seeder;

class ExcelSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ExcelSetting::query()->firstOrCreate([
            'spreadsheetId' => '1SUk8PHE8tWLbBi5Z5K4GmRN5p2NGoarj0ZHha6LDCYc',
            'range' => 'Sheet2!A:F',
        ]);
    }
}
