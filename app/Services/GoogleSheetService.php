<?php

namespace App\Services;

use App\Models\ExcelSetting;
use Google\Client;
use Google\Service\Sheets;

class GoogleSheetService
{
    public static function appendRow(array $row)
    {
        $client = new Client();
        $client->setApplicationName('Laravel Google Sheet');
        $client->setScopes([Sheets::SPREADSHEETS]);
        $client->setAuthConfig(storage_path('app/google/service-account.json'));
        $client->setAccessType('offline');

        $service = new Sheets($client);
        $excel = ExcelSetting::firstOrFail();
        $spreadsheetId = $excel->spreadsheetId;
        // $spreadsheetId = '1SUk8PHE8tWLbBi5Z5K4GmRN5p2NGoarj0ZHha6LDCYc';

        $range = $excel->range;

        $body = new \Google\Service\Sheets\ValueRange([
            'values' => [$row]
        ]);

        $params = [
            'valueInputOption' => 'RAW'
        ];

        $service->spreadsheets_values->append(
            $spreadsheetId,
            $range,
            $body,
            $params
        );
    }
}
