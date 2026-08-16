<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\City;
use App\Models\State;
use App\Models\Country;
use Illuminate\Http\Request;

class InstitutionController extends Controller
{
    public function get_institutions_by_location(Request $request)
    {
        $countryId = $request->country_id;
        $stateId = $request->state_id;
        $cityId = $request->city_id;
        $type = $request->type; // 'University', 'College', etc.
        
        $query = \App\Models\Institution::where('is_active', true);

        if ($countryId) {
            $query->where('country_id', $countryId);
        }
        
        if ($stateId) {
            $query->where('state_id', $stateId);
        }
        
        if ($cityId) {
            $query->where('city_id', $cityId);
        }
        
        if ($type) {
            $query->where('type', $type);
        }

        $institutions = $query->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'city_id']);

        return response()->json([
            'success' => true,
            'institutions' => $institutions
        ]);
    }
}