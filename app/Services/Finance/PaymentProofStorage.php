<?php

namespace App\Services\Finance;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentProofStorage
{
    /**
     * Store an uploaded payment proof (payment screenshot) on the private disk.
     */
    public function store(?UploadedFile $file): ?string
    {
        return $file instanceof UploadedFile ? $file->store('payment-proofs', 'local') : null;
    }

    public function delete(?string $path): void
    {
        if (! empty($path)) {
            $this->disk()->delete($path);
        }
    }

    public function exists(?string $path): bool
    {
        return ! empty($path) && $this->disk()->exists($path);
    }

    public function response(string $path): StreamedResponse
    {
        return $this->disk()->response($path);
    }

    private function disk(): FilesystemAdapter
    {
        return Storage::disk('local');
    }
}
