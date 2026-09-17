<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ChannexService
{
    protected string $baseUrl;

    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.channex.base_url'),
            '/'
        );

        $this->apiKey = config(
            'services.channex.api_key'
        );
    }

    protected function client(): PendingRequest
    {
        return Http::withHeaders([
            'user-api-key' => $this->apiKey,
            'Accept' => 'application/json',
        ])
            ->timeout(15);
    }

    /**
     * Test API connection
     */
    public function testConnection(): array
    {
        return $this->properties();
    }

    /**
     * Get properties available for this API Key.
     */
    public function properties(): array
    {
        return $this->client()
            ->get(
                $this->baseUrl . '/properties/options'
            )
            ->throw()
            ->json();
    }

    /**
     * Get Property details.
     */
    public function property(
        string $propertyId
    ): array {
        return $this->client()
            ->get(
                $this->baseUrl .
                '/properties/' .
                $propertyId
            )
            ->throw()
            ->json();
    }

    /**
     * Get Room Types for a Property.
     */
    public function roomTypes(
        string $propertyId
    ): array {
        return $this->client()
            ->get(
                $this->baseUrl . '/room_types',
                [
                    'filter[property_id]' =>
                        $propertyId,
                ]
            )
            ->throw()
            ->json();
    }

    /**
     * Get Rate Plans for a Property.
     */
    public function ratePlans(
        string $propertyId
    ): array {
        return $this->client()
            ->get(
                $this->baseUrl .
                '/rate_plans/options',
                [
                    'filter[property_id]' =>
                        $propertyId,
                ]
            )
            ->throw()
            ->json();
    }


    public function updateAvailability(array $values): array
    {
        return $this->client()
            ->post(
                $this->baseUrl . '/availability',
                [
                    'values' => $values,
                ]
            )
            ->throw()
            ->json();
    }

    public function updateRestrictions(
        array $values
    ): array {
        return $this->client()
            ->post(
                $this->baseUrl .
                '/restrictions',
                [
                    'values' => $values,
                ]
            )
            ->throw()
            ->json();
    }
}