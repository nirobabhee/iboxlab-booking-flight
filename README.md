# Flight Booking Search API

Hi there,
This is a small Laravel API project for a flight booking task. I tried to keep the code simple, readable, and easy to test without depending on any real third-party flight API.

The API searches flights from 3 mock providers, converts their different response formats into one common response, removes duplicate flights, shows the best price, and lets a user create a booking from a selected flight.

---

## What This Project Does

In normal words:

- A user searches flights like `DAC` to `DXB`.
- The system checks 3 mock providers.
- Each provider gives data in a different format.
- The app converts all provider data into one clean format.
- Duplicate flights are merged, so the user does not see the same flight again and again.
- The cheapest provider price is shown first.
- User can book a flight using the returned `flight_id`.
- If a flight is already confirmed, the same flight cannot be booked again.

This makes the API easier for frontend developers, mobile app developers, or reviewers to understand and use.

---

## Project Structure

Laravel application code is inside the `core` folder.

The most important files are:

| File | Purpose |
| --- | --- |
| `core/routes/api.php` | API route list |
| `core/app/Http/Controllers/Api/FlightController.php` | Handles flight search request |
| `core/app/Http/Controllers/Api/BookingController.php` | Handles booking create and booking details |
| `core/app/Services/FlightSearchService.php` | Mock providers, data normalization, duplicate merge, sorting, filtering |
| `core/app/Models/Booking.php` | Booking model |
| `core/database/migrations/2026_06_19_000001_create_bookings_table.php` | Booking table migration |
| `core/app/Constants/Status.php` | Booking status values |
| `core/app/Http/helpers/helpers.php` | Booking response, helper |

---

## How To Run The Project

Open terminal inside the project and go to Laravel folder:

```bash
cd core
```

Install composer packages if they are not installed yet:

```bash
composer install
```

Check your database settings in `core/.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=iboxlab-excercise
DB_USERNAME=root
DB_PASSWORD=
```

Create the booking table:

```bash
php artisan migrate
```

Run the project:
[localhost] (http://127.0.0.1/project_name/api)

```bash
php artisan serve
```

Default local API URL:

```text
http://127.0.0.1:8000/api
```

If you are using Laragon, you may also use your local virtual host URL.

---

## Testing Flow

Please test in this order:

1. Search flights.
2. Copy a `flight_id` from the search result.
3. Create a booking using that `flight_id`.
4. Copy the `booking_reference`.
5. Check booking details.
6. Try booking the same flight again to see duplicate booking validation.

This flow is important because `flight_id` is generated from actual normalized flight data. Please do not write a custom flight id manually.

---

## API 1: Search Flights

### Endpoint

```http
GET /api/flights/search
```

### Full URL Example

```http
GET http://127.0.0.1/your_project/api/flights/search?from=DAC&to=DXB&date=2026-07-01&passengers=2
```

### Query Parameters

| Name | Required | Example | Note |
| --- | --- | --- | --- |
| `from` | Yes | `DAC` | Origin airport |
| `to` | Yes | `DXB` | Destination airport |
| `date` | Yes | `2026-07-01` | Flight date |
| `passengers` | No | `2` | Default is `1` |


| `carrier` | No | `EK` | Filter by airline |
| `max_price` | No | `300` | Max price per passenger |
| `sort_by` | No | `price` | `price`, `depart`, `arrive`,
| `sort_direction` | No | `asc` | `asc` or `desc` |

### Example With Filter

```http
GET http://127.0.0.1/your_project/api/flights/search?from=DAC&to=DXB&date=2026-07-01&passengers=2&nonstop=true&sort_by=price
```

### Search Response Example

```json
{
  "remark": "flight_search",
  "status": "success",
  "message": {
    "success": [
      "Flight search result"
    ]
  },
  "data": {
    "flights": [
      {
        "flight_id": "2dac5acc5ed8a71d90d447f41800e2fdbd14bdb2",
        "carrier": "BS",
        "flight_no": "BS118",
        "from": "DAC",
        "to": "DXB",
        "depart_at": "2026-07-01T14:30:00",
        "arrive_at": "2026-07-01T19:20:00",
        "stops": 1,
        "duration_minutes": 290,
        "currency": "USD",
        "best_provider": "provider_b",
        "price_per_passenger": 265,
        "passengers": 2,
        "total_price": 530,
        "provider_options": [
          {
            "provider": "provider_b",
            "price_usd": 265,
            "currency": "USD"
          }
        ],
        "provider_count": 1
      }
    ],
    "meta": {
      "searched_providers": 3,
      "successful_providers": 3,
      "failed_providers": 0,
      "raw_result_count": 10,
      "unique_result_count": 6,
      "returned_result_count": 6,
      "duplicates_merged": 4,
      "currency": "USD"
    }
  }
}
```

### What To Notice In Search Response

- `flight_id` is the value needed for booking.
- `best_provider` shows which provider has the cheapest price for that flight.
- `provider_options` keeps provider price details, useful when the same flight comes from multiple providers.
- `meta` explains how complete the result is.
- `duplicates_merged` shows how many repeated flights were hidden from the user.

---

## Demo Flights

For quick testing, search this route:

```text
DAC to DXB on 2026-07-01
```

Current demo flights:

| Flight | Departure | Stops | Best Price |
| --- | --- | --- | --- |
| `BS118` | `2026-07-01T14:30:00` | `1` | `$265` |
| `CJ300` | `2026-07-01T06:00:00` | `2` | `$270` |
| `AA205` | `2026-07-01T22:10:00` | `0` | `$280` |
| `BS220` | `2026-07-01T09:15:00` | `1` | `$295` |
| `AA101` | `2026-07-01T08:00:00` | `0` | `$320` |
| `EK585` | `2026-07-01T03:45:00` | `0` | `$399` |

Please copy the real `flight_id` from the API response before booking.

---

## API 2: Create Booking

### Endpoint

```http
POST /api/bookings
```

### Full URL Example

```http
POST http://127.0.0.1/your_project/api/bookings
or
POST http://127.0.0.1:8000/api/bookings
```

### Headers

```http
Content-Type: application/json
Accept: application/json
```

### Request Body Example

```json
{
  "flight_id": "85b16d0a6a9dab9593c4d3d14cf0e9ad3636035b",
  "from": "DAC",
  "to": "DXB",
  "date": "2026-07-01",
  "passengers": 2,
  "contact_name": "Nirob Abhee",
  "contact_email": "nirob@example.com",
  "contact_phone": "01700000000"
}
```

### Successful Booking Response

```json
{
  "remark": "booking_created",
  "status": "success",
  "message": {
    "success": [
      "Booking created successfully"
    ]
  },
  "data": {
    "booking": {
      "booking_reference": "BKXXXXXXXXXX",
      "flight_id": "85b16d0a6a9dab9593c4d3d14cf0e9ad3636035b",
      "carrier": "EK",
      "flight_no": "EK585",
      "from": "DAC",
      "to": "DXB",
      "passengers": 2,
      "total_price": 798,
      "currency": "USD",
      "status": 1
    }
  }
}
```

Keep the `booking_reference`. It is used to check the booking later.

---

## Duplicate Booking Rule

If a flight is already booked and confirmed, the same flight cannot be booked again.

This is added because in real booking systems the same confirmed inventory should not be sold twice.

### Duplicate Booking Error

```json
{
  "remark": "flight_already_booked",
  "status": "error",
  "message": {
    "error": [
      "This flight is already booked and confirmed. Please try another flight"
    ]
  },
  "data": {
    "booking_reference": "BKXXXXXXXXXX",
    "flight_id": "85b16d0a6a9dab9593c4d3d14cf0e9ad3636035b",
    "status": 1
  }
}
```

Booking status values:

| Status | Meaning |
| --- | --- |
| `1` | Confirmed |
| `2` | Cancelled |
| `3` | Refunded |

---

## API 3: Check Booking Details

### Endpoint

```http
GET /api/bookings/{reference}
```

### Full URL Example

```http
GET http://127.0.0.1:8000/api/bookings/BKXXXXXXXXXX
```

### Response Example

```json
{
  "remark": "booking_details",
  "status": "success",
  "message": {
    "success": [
      "Booking details"
    ]
  },
  "data": {
    "booking": {
      "booking_reference": "BKXXXXXXXXXX",
      "flight_no": "EK585",
      "from": "DAC",
      "to": "DXB",
      "depart_at": "2026-07-01T03:45:00",
      "arrive_at": "2026-07-01T06:50:00",
      "passengers": 2,
      "total_price": 798,
      "currency": "USD",
      "status": 1
    }
  }
}
```

---

## Common Mistake Example

This request will not work:

```json
{
  "flight_id": "EK_EK585_20260701T034500_DAC_DXB",
  "from": "DAC",
  "to": "CXB",
  "date": "2026-06-20",
  "passengers": 2
}
```

Reason:

- `flight_id` was manually written, not copied from search response.
- `to` is `CXB`, but mock data has `DXB`.
- `date` is `2026-06-20`, but demo flights are on `2026-07-01`.

Correct habit:

```text
Search first → copy flight_id → book with same route/date/passenger data
```

---

## How The Main Code Works

### Flight Search Logic

The logic lives in:

```text
core/app/Services/FlightSearchService.php
```

The service has 3 mock providers:

- Provider A returns data under `flights`
- Provider B returns data under `data`
- Provider C returns data under `results`

The service normalizes all of them into one common flight structure.

After that it:

1. Filters by `from`, `to`, and `date`.
2. Creates a stable `flight_id`.
3. Groups duplicate flights.
4. Picks the cheapest provider as the best option.
5. Returns provider completeness information in `meta`.

### Booking Logic

The booking logic lives in:

```text
core/app/Http/Controllers/Api/BookingController.php
```

Before saving a booking, the code checks if this flight is already confirmed:

```php
Booking::where('flight_id', $flight['flight_id'])
    ->where('status', Status::BOOKING_CONFIRMED)
    ->first();
```

If it finds a confirmed booking, it returns an error and asks the user to choose another flight.

---

## Reviewer Checklist

You can verify the full task with these quick steps:

- Run `php artisan migrate`
- Run `php artisan serve`
- Search flights using `GET /api/flights/search?from=DAC&to=DXB&date=2026-07-01&passengers=2`
- Copy a `flight_id`
- Create booking using `POST /api/bookings`
- Copy `booking_reference`
- Check booking using `GET /api/bookings/{booking_reference}`
- Try the same booking again
- Confirm the API returns `flight_already_booked`

---

## Requirement Coverage

| Requirement | Status |
| --- | --- |
| Search from 3 providers | Done |
| Different provider schemas | Done |
| Unified API response | Done |
| Stable `flight_id` | Done |
| Duplicate flight merge | Done |
| Best price display | Done |
| Sorting and filtering | Done |
| Completeness information | Done |
| Save booking to database | Done |
| Retrieve booking details | Done |
| Stop duplicate confirmed booking | Done |

---

## Final Note

The project is intentionally written in a simple Laravel style, so another developer can open the controller, service, and migration files and quickly understand the flow.

No real flight API is used here. The providers are mocked locally inside the service, which keeps the task easy to run and review.
