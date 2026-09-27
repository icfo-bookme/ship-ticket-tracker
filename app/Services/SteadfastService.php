<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SteadfastService
{
    protected $apiKey;

    protected $secretKey;

    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('steadfast.api_key'); // from config/steadfast.php
        $this->secretKey = config('steadfast.secret_key');
        $this->baseUrl = config('steadfast.base_url');
    }

    protected function headers()
    {
        return [
            'Api-Key' => $this->apiKey,
            'Secret-Key' => $this->secretKey,
            'Content-Type' => 'application/json',
        ];
    }

    public function createOrder(array $data): array
    {
        $response = Http::withHeaders($this->headers())
            ->post($this->baseUrl.'/create_order', $data);

        if (! $response->successful()) {
            throw new RuntimeException('Steadfast parcel create failed with HTTP '.$response->status().'.');
        }

        return $response->json() ?? [];
    }

    public function bulkCreate(array $data): array
    {
        $response = Http::withHeaders($this->headers())
            ->post($this->baseUrl.'/create_order/bulk-order', [
                'data' => json_encode($data, JSON_THROW_ON_ERROR),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Steadfast bulk create failed with HTTP '.$response->status().'.');
        }

        return $response->json() ?? [];
    }

    public function statusByInvoice(string $invoice): array
    {
        $response = Http::withHeaders($this->headers())
            ->get($this->baseUrl.'/status_by_invoice/'.urlencode($invoice));

        return [
            'http_status' => $response->status(),
            'body' => $response->json() ?? [],
        ];
    }

    public function statusCheck(int|string $id): array
    {
        $response = Http::withHeaders($this->headers())
            ->get($this->baseUrl.'/status_by_cid/'.$id);

        return $response->json() ?? [];
    }
}
