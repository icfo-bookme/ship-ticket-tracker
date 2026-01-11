<?php

namespace App\Services;

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

        // ✅ আপনার Spreadsheet ID এখানে বসানো হয়েছে
        $spreadsheetId = '1SUk8PHE8tWLbBi5Z5K4GmRN5p2NGoarj0ZHha6LDCYc';

        // Sheet name ঠিক রাখুন (Sheet1 হলে Sheet1)
        $range = 'Sheet1!A:E';

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
