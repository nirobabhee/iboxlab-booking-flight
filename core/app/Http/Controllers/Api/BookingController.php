<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\FlightSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Constants\Status;

class BookingController extends Controller
{
    public function store(Request $request, FlightSearchService $flightSearchService)
    {
        $validator = Validator::make($request->all(), [
            'flight_id' => 'required|string',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
            'date' => 'required|date_format:Y-m-d',
            'passengers' => 'required|integer|min:1|max:9',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $flight = $flightSearchService->findFlight(
            $request->flight_id,
            $request->from,
            $request->to,
            $request->date,
            (int) $request->passengers
        );

        if (!$flight) {
            $notify[] = 'Flight not found or expired';
            return responseError('flight_not_found', $notify);
        }

        $confirmedBooking = Booking::where('flight_id', $flight['flight_id'])
            ->where('status', Status::BOOKING_CONFIRMED)
            ->first();

        if ($confirmedBooking) {
            $notify[] = 'This flight is already booked and confirmed. Please try another flight';
            return responseError('flight_already_booked', $notify, [
                'booking_reference' => $confirmedBooking->booking_reference,
                'flight_id' => $confirmedBooking->flight_id,
                'status' => $confirmedBooking->status,
            ]);
        }

        $booking = new Booking();
        $booking->booking_reference = $this->bookingReference();
        $booking->user_id = auth()->id() ?? 1; // Assuming user authentication is handled, else default to 1 for testing    
        $booking->flight_id = $flight['flight_id'];
        $booking->provider = $flight['best_provider'];
        $booking->carrier = $flight['carrier'];
        $booking->flight_no = $flight['flight_no'];
        $booking->from_airport = $flight['from'];
        $booking->to_airport = $flight['to'];
        $booking->depart_at = str_replace('T', ' ', $flight['depart_at']);
        $booking->arrive_at = str_replace('T', ' ', $flight['arrive_at']);
        $booking->stops = $flight['stops'];
        $booking->passengers = $flight['passengers'];
        $booking->price_per_passenger = $flight['price_per_passenger'];
        $booking->total_price = $flight['total_price'];
        $booking->currency = $flight['currency'];
        $booking->provider_options = $flight['provider_options'];
        $booking->contact_name = $request->contact_name;
        $booking->contact_email = $request->contact_email;
        $booking->contact_phone = $request->contact_phone;
        $booking->status = Status::BOOKING_CONFIRMED;
        $booking->save();

        $notify[] = 'Booking created successfully';
        return responseSuccess('booking_created', $notify, [
            'booking' => $this->bookingResponse($booking),
        ]);
    }

    public function show($reference)
    {
        $booking = Booking::where('booking_reference', $reference)->first();

        if (!$booking) {
            $notify[] = 'Booking not found';
            return responseError('booking_not_found', $notify);
        }

        $notify[] = 'Booking details';
        return responseSuccess('booking_details', $notify, [
            'booking' => $this->bookingResponse($booking),
        ]);
    }

    private function bookingReference(): string
    {
        $prefix= gs('booking_ref_prefix') ?? 'BK';
        do {
            $reference = $prefix . getTrx();
        } while (Booking::where('booking_reference', $reference)->exists());

        return $reference;
    }

    private function bookingResponse(Booking $booking): array
    {
        return [
            'booking_reference' => $booking->booking_reference,
            'flight_id' => $booking->flight_id,
            'provider' => $booking->provider,
            'carrier' => $booking->carrier,
            'flight_no' => $booking->flight_no,
            'from' => $booking->from_airport,
            'to' => $booking->to_airport,
            'depart_at' => optional($booking->depart_at)->format('Y-m-d\TH:i:s'),
            'arrive_at' => optional($booking->arrive_at)->format('Y-m-d\TH:i:s'),
            'stops' => $booking->stops,
            'passengers' => $booking->passengers,
            'price_per_passenger' => (float) $booking->price_per_passenger,
            'total_price' => (float) $booking->total_price,
            'currency' => $booking->currency,
            'provider_options' => $booking->provider_options,
            'contact_name' => $booking->contact_name,
            'contact_email' => $booking->contact_email,
            'contact_phone' => $booking->contact_phone,
            'status' => $booking->status,
            'booked_at' => optional($booking->created_at)->toDateTimeString(),
        ];
    }
}
