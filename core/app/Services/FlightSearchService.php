<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class FlightSearchService
{
    public function search(array $params): array
    {
        $passengers = (int) ($params['passengers'] ?? 1);
        $providers = $this->providerResults();
        $allFlights = collect();
        $providerStatus = [];

        foreach ($providers as $providerName => $providerFlights) {
            $normalizedFlights = collect($providerFlights)->map(function ($flight) use ($providerName) {
                return $this->normalizeFlight($providerName, $flight);
            });

            $providerStatus[] = [
                'provider' => $providerName,
                'status' => 'ok',
                'result_count' => $normalizedFlights->count(),
            ];

            $allFlights = $allFlights->merge($normalizedFlights);
        }

        $requestedFlights = $allFlights->filter(function ($flight) use ($params) {
            return $flight['from'] == strtoupper($params['from'])
                && $flight['to'] == strtoupper($params['to'])
                && Carbon::parse($flight['depart_at'])->format('Y-m-d') == $params['date'];
        });

        $mergedFlights = $this->mergeDuplicateFlights($requestedFlights, $passengers);
        $filteredFlights = $this->applyFilters($mergedFlights, $params);
        $sortedFlights = $this->sortFlights($filteredFlights, $params);

        return [
            'flights' => $sortedFlights->values()->toArray(),
            'meta' => [
                'searched_providers' => count($providers),
                'successful_providers' => count($providers),
                'failed_providers' => 0,
                'provider_status' => $providerStatus,
                'raw_result_count' => $requestedFlights->count(),
                'unique_result_count' => $mergedFlights->count(),
                'returned_result_count' => $sortedFlights->count(),
                'duplicates_merged' => $requestedFlights->count() - $mergedFlights->count(),
                'currency' => 'USD',
                'generated_at' => now()->toDateTimeString(),
            ],
        ];
    }

    public function findFlight(string $flightId, string $from, string $to, string $date, int $passengers): ?array
    {
        $result = $this->search([
            'from' => $from,
            'to' => $to,
            'date' => $date,
            'passengers' => $passengers,
        ]);

        return collect($result['flights'])->firstWhere('flight_id', $flightId);
    }

    private function providerResults(): array
    {
        return [
            'provider_a' => $this->providerA(),
            'provider_b' => $this->providerB(),
            'provider_c' => $this->providerC(),
        ];
    }

    private function normalizeFlight(string $provider, array $flight): array
    {
        if ($provider == 'provider_a') {
            $departAt = Carbon::parse($flight['depart']);
            $arriveAt = Carbon::parse($flight['arrive']);

            return $this->flightData($provider, $flight['carrier'], $flight['from'], $flight['to'], $departAt, $arriveAt, $flight['stops'], $flight['fare_usd'], 'USD', $flight['flight_no']);
        }

        if ($provider == 'provider_b') {
            $departAt = Carbon::parse($flight['departure_time']);
            $arriveAt = Carbon::parse($flight['arrival_time']);

            return $this->flightData($provider, $flight['airline_code'], $flight['origin'], $flight['destination'], $departAt, $arriveAt, $flight['segments'], $flight['price']['amount'], $flight['price']['currency'], $flight['number']);
        }

        $departAt = Carbon::createFromTimestampUTC($flight['times']['dep']);
        $arriveAt = Carbon::createFromTimestampUTC($flight['times']['arr']);

        return $this->flightData($provider, $flight['iata'], $flight['route']['src'], $flight['route']['dst'], $departAt, $arriveAt, $flight['layovers'], $flight['total_price'], $flight['currency'], $flight['code']);
    }

    private function flightData($provider, $carrier, $from, $to, Carbon $departAt, Carbon $arriveAt, $stops, $price, $currency, $flightNo): array
    {
        $depart = $departAt->format('Y-m-d H:i:s');
        $arrive = $arriveAt->format('Y-m-d H:i:s');
        $flightId = sha1(strtoupper($carrier) . '|' . strtoupper($flightNo) . '|' . strtoupper($from) . '|' . strtoupper($to) . '|' . $depart . '|' . $arrive);

        return [
            'flight_id' => $flightId,
            'provider' => $provider,
            'carrier' => strtoupper($carrier),
            'from' => strtoupper($from),
            'to' => strtoupper($to),
            'depart_at' => $depart,
            'arrive_at' => $arrive,
            'stops' => (int) $stops,
            'price_usd' => (float) $price,
            'currency' => strtoupper($currency),
            'flight_no' => strtoupper($flightNo),
            'duration_minutes' => $departAt->diffInMinutes($arriveAt),
        ];
    }

    private function mergeDuplicateFlights(Collection $flights, int $passengers): Collection
    {
        return $flights->groupBy('flight_id')->map(function ($sameFlights) use ($passengers) {
            $bestFlight = $sameFlights->sortBy('price_usd')->first();
            $providerOptions = $sameFlights->sortBy('price_usd')->map(function ($flight) {
                return [
                    'provider' => $flight['provider'],
                    'price_usd' => $flight['price_usd'],
                    'currency' => $flight['currency'],
                ];
            })->values()->toArray();

            return [
                'flight_id' => $bestFlight['flight_id'],
                'carrier' => $bestFlight['carrier'],
                'flight_no' => $bestFlight['flight_no'],
                'from' => $bestFlight['from'],
                'to' => $bestFlight['to'],
                'depart_at' => Carbon::parse($bestFlight['depart_at'])->format('Y-m-d\TH:i:s'),
                'arrive_at' => Carbon::parse($bestFlight['arrive_at'])->format('Y-m-d\TH:i:s'),
                'stops' => $bestFlight['stops'],
                'duration_minutes' => $bestFlight['duration_minutes'],
                'currency' => 'USD',
                'best_provider' => $bestFlight['provider'],
                'price_per_passenger' => $bestFlight['price_usd'],
                'passengers' => $passengers,
                'total_price' => $bestFlight['price_usd'] * $passengers,
                'provider_options' => $providerOptions,
                'provider_count' => count($providerOptions),
            ];
        })->values();
    }

    private function applyFilters(Collection $flights, array $params): Collection
    {
        if (!empty($params['carrier'])) {
            $flights = $flights->where('carrier', strtoupper($params['carrier']));
        }

        if (isset($params['stops']) && $params['stops'] !== '') {
            $flights = $flights->where('stops', (int) $params['stops']);
        }

        if (!empty($params['max_price'])) {
            $flights = $flights->filter(function ($flight) use ($params) {
                return $flight['price_per_passenger'] <= (float) $params['max_price'];
            });
        }

        if (filter_var($params['nonstop'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $flights = $flights->where('stops', 0);
        }

        return $flights;
    }

    private function sortFlights(Collection $flights, array $params): Collection
    {
        $sortBy = $params['sort_by'] ?? 'price';
        $sortDirection = $params['sort_direction'] ?? 'asc';
        $columns = [
            'price' => 'price_per_passenger',
            'depart' => 'depart_at',
            'arrive' => 'arrive_at',
            'duration' => 'duration_minutes',
            'stops' => 'stops',
        ];
        $column = $columns[$sortBy] ?? 'price_per_passenger';

        return $sortDirection == 'desc' ? $flights->sortByDesc($column) : $flights->sortBy($column);
    }

    private function providerA(): array
    {
        //1, If use API {ProviderA} then process here for return data in the same format as below 
        return [
            ['carrier' => 'AA', 'from' => 'DAC', 'to' => 'DXB', 'depart' => '2026-07-01T08:00:00', 'arrive' => '2026-07-01T12:30:00', 'stops' => 0, 'fare_usd' => 320.00, 'flight_no' => 'AA101'],
            ['carrier' => 'AA', 'from' => 'DAC', 'to' => 'DXB', 'depart' => '2026-07-01T22:10:00', 'arrive' => '2026-07-02T02:40:00', 'stops' => 0, 'fare_usd' => 280.00, 'flight_no' => 'AA205'],
            ['carrier' => 'BS', 'from' => 'DAC', 'to' => 'DXB', 'depart' => '2026-07-01T09:15:00', 'arrive' => '2026-07-01T15:00:00', 'stops' => 1, 'fare_usd' => 310.00, 'flight_no' => 'BS220'],
            ['carrier' => 'EK', 'from' => 'DAC', 'to' => 'DXB', 'depart' => '2026-07-01T03:45:00', 'arrive' => '2026-07-01T06:50:00', 'stops' => 0, 'fare_usd' => 410.00, 'flight_no' => 'EK585'],
        ];
    }

    private function providerB(): array
    {
        //2, If use API {ProviderB} then process here for return data in the same format as below 
        return [
            ['airline_code' => 'BS', 'origin' => 'DAC', 'destination' => 'DXB', 'departure_time' => '2026-07-01 09:15', 'arrival_time' => '2026-07-01 15:00', 'segments' => 1, 'price' => ['amount' => 295, 'currency' => 'USD'], 'number' => 'BS220'],
            ['airline_code' => 'BS', 'origin' => 'DAC', 'destination' => 'DXB', 'departure_time' => '2026-07-01 14:30', 'arrival_time' => '2026-07-01 19:20', 'segments' => 1, 'price' => ['amount' => 265, 'currency' => 'USD'], 'number' => 'BS118'],
            ['airline_code' => 'EK', 'origin' => 'DAC', 'destination' => 'DXB', 'departure_time' => '2026-07-01 03:45', 'arrival_time' => '2026-07-01 06:50', 'segments' => 0, 'price' => ['amount' => 399, 'currency' => 'USD'], 'number' => 'EK585'],
        ];
    }

    private function providerC(): array
    {
        //3, If use API {ProviderC} then process here for return data in the same format as below 
        return [
            ['iata' => 'AA', 'route' => ['src' => 'DAC', 'dst' => 'DXB'], 'times' => ['dep' => 1782892800, 'arr' => 1782909000], 'layovers' => 0, 'total_price' => 335, 'currency' => 'USD', 'code' => 'AA101'],
            ['iata' => 'CJ', 'route' => ['src' => 'DAC', 'dst' => 'DXB'], 'times' => ['dep' => 1782885600, 'arr' => 1782903600], 'layovers' => 2, 'total_price' => 270, 'currency' => 'USD', 'code' => 'CJ300'],
            ['iata' => 'EK', 'route' => ['src' => 'DAC', 'dst' => 'DXB'], 'times' => ['dep' => 1782877500, 'arr' => 1782888600], 'layovers' => 0, 'total_price' => 405, 'currency' => 'USD', 'code' => 'EK585'],
        ];
    }
}
