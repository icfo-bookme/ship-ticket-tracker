<?php

namespace App\Services;

use App\Models\ExcelSetting;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\AddSheetRequest;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\Request;

class GoogleSheetService
{
    public static function appendRow(array $row): void
    {
        $client = new Client;
        $client->setApplicationName('Laravel Google Sheet');
        $client->setScopes([Sheets::SPREADSHEETS]);
        $client->setAuthConfig(storage_path('app/google/service-account.json'));
        $client->setAccessType('offline');

        $service = new Sheets($client);
        $excel = ExcelSetting::firstOrFail();
        $spreadsheetId = $excel->spreadsheetId;
        // $spreadsheetId = '1SUk8PHE8tWLbBi5Z5K4GmRN5p2NGoarj0ZHha6LDCYc';

        $configuredRange = $excel->range;
        $columns = str_contains($configuredRange, '!')
            ? substr($configuredRange, strpos($configuredRange, '!') + 1)
            : 'A:F';
        $sheetTitle = now()->toDateString();
        $range = $sheetTitle.'!'.$columns;

        self::ensureSheetExists($service, $spreadsheetId, $sheetTitle);

        $row = array_values(array_map(
            static fn (mixed $value): mixed => $value ?? '',
            $row,
        ));

        $body = new \Google\Service\Sheets\ValueRange([
            'values' => [$row],
        ]);

        $params = [
            'valueInputOption' => 'RAW',
        ];

        $service->spreadsheets_values->append(
            $spreadsheetId,
            $range,
            $body,
            $params
        );
    }

    private static function ensureSheetExists(Sheets $service, string $spreadsheetId, string $sheetTitle): void
    {
        $spreadsheet = $service->spreadsheets->get($spreadsheetId, ['fields' => 'sheets.properties.title']);
        $sheetExists = collect($spreadsheet->getSheets())
            ->contains(fn ($sheet): bool => $sheet->getProperties()->getTitle() === $sheetTitle);

        if ($sheetExists) {
            return;
        }

        $service->spreadsheets->batchUpdate(
            $spreadsheetId,
            new BatchUpdateSpreadsheetRequest([
                'requests' => [
                    new Request([
                        'addSheet' => new AddSheetRequest([
                            'properties' => ['title' => $sheetTitle],
                        ]),
                    ]),
                ],
            ])
        );
    }
}
