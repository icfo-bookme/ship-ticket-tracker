<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

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

    // Bulk Order
    public function bulkCreate(array $data)
    {
        $response = Http::withHeaders($this->headers())
            ->post($this->baseUrl . '/create_order/bulk-order', [
                'data' => json_encode($data)
            ]);

        return $response->json();
    }

    public function statusCheck($id)
    {
        $response = Http::withHeaders($this->headers())
            ->get($this->baseUrl . '/status_by_cid/{$id}');

        return $response->json();
    }
}
