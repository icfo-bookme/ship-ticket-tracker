<?php

namespace App\Services\Sales;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoogleDriveTicketService
{
    public function streamPdf(string $filename): StreamedResponse
    {
        $file = $this->findPdf($filename);

        if (! $file) {
            abort(404, 'PDF not found in Google Drive');
        }

        $response = $this->drive()->files->get($file->getId(), ['alt' => 'media']);

        return response()->stream(
            function () use ($response): void {
                echo $response->getBody()->getContents();
            },
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$filename}\"",
            ]
        );
    }

    public function redirectToPrintView(string $filename): RedirectResponse
    {
        $file = $this->findPdf($filename);

        if (! $file) {
            return redirect()->back()->with('error', 'Ticket not found in Google Drive');
        }

        return redirect()->away("https://drive.google.com/file/d/{$file->getId()}/view?print=true");
    }

    private function findPdf(string $filename): ?DriveFile
    {
        $files = $this->drive()->files->listFiles([
            'q' => "name='{$filename}' and '{$this->folderId()}' in parents and mimeType='application/pdf' and trashed=false",
            'fields' => 'files(id,name)',
            'pageSize' => 1,
        ]);

        return $files->getFiles()[0] ?? null;
    }

    private function drive(): Drive
    {
        $client = new Client;
        $client->setAuthConfig(storage_path('app/google/service-account.json'));
        $client->addScope(Drive::DRIVE_READONLY);

        return new Drive($client);
    }

    private function folderId(): string
    {
        return config('services.google_drive.ticket_folder_id', '1Kw6lNhhch4H0SbXrNNNRWp_4mTEGvvCv');
    }
}
