<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FlightSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FlightController extends Controller
{
    public function search(Request $request, FlightSearchService $flightSearchService)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
            'date' => 'required|date_format:Y-m-d',
            'passengers' => 'nullable|integer|min:1|max:9',
            'sort_by' => 'nullable|in:price,depart,arrive,duration,stops',
            'sort_direction' => 'nullable|in:asc,desc',
            'carrier' => 'nullable|string|size:2',
            'stops' => 'nullable|integer|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'nonstop' => 'nullable',
        ]);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $result = $flightSearchService->search($request->all());

        $notify[] = 'Flight search result';
        return responseSuccess('flight_search', $notify, [
            'flights' => $result['flights'],
            'meta' => $result['meta'],
        ]);
    }
}
