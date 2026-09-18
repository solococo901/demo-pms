<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ChannexBookingApiService
{
    private function client(): PendingRequest
    {
        $baseUrl = rtrim(
            (string) config('services.channex.base_url'),
            '/'
        );

        $apiKey = (string) config(
            'services.channex.api_key'
        );

        if ($baseUrl === '') {
            throw new RuntimeException(
                'CHANNEX_BASE_URL is not configured.'
            );
        }

        if ($apiKey === '') {
            throw new RuntimeException(
                'CHANNEX_API_KEY is not configured.'
            );
        }

        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'user-api-key' => $apiKey,
            ])
            ->timeout(30)
            ->retry(
                2,
                500,
                throw: false
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Booking Revision Feed
    |--------------------------------------------------------------------------
    |
    | Returns unacknowledged booking revisions, oldest first.
    |
    */
    public function getRevisionFeed(): array
    {
        $response = $this->client()->get(
            '/booking_revisions/feed',
            [
                'order' => [
                    'inserted_at' => 'asc',
                ],
            ]
        );

        if (!$response->successful()) {
            throw new RuntimeException(
                'Channex Booking Revision Feed failed. HTTP '
                . $response->status()
                . ': '
                . $response->body()
            );
        }

        $data = $response->json('data');

        return is_array($data)
            ? $data
            : [];
    }

    /*
    |--------------------------------------------------------------------------
    | Get One Booking Revision
    |--------------------------------------------------------------------------
    */
    public function getRevision(
        string $revisionId
    ): array {
        $response = $this->client()->get(
            '/booking_revisions/'
            . urlencode($revisionId)
        );

        if (!$response->successful()) {
            throw new RuntimeException(
                "Unable to get Channex booking revision {$revisionId}. HTTP "
                . $response->status()
                . ': '
                . $response->body()
            );
        }

        $data = $response->json('data');

        if (!is_array($data)) {
            throw new RuntimeException(
                "Channex booking revision {$revisionId} returned no data."
            );
        }

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Acknowledge Booking Revision
    |--------------------------------------------------------------------------
    |
    | Only call this after the revision has been saved successfully in PMS.
    |
    */
    public function acknowledge(
        string $revisionId
    ): void {
        $response = $this->client()->post(
            '/booking_revisions/'
            . urlencode($revisionId)
            . '/ack',
            []
        );

        if (!$response->successful()) {
            throw new RuntimeException(
                "Unable to acknowledge Channex booking revision {$revisionId}. HTTP "
                . $response->status()
                . ': '
                . $response->body()
            );
        }
    }
}
