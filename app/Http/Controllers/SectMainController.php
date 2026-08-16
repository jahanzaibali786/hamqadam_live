<?php

namespace App\Http\Controllers;

use App\Models\SectMain;
use App\Models\Religion;
use Illuminate\Http\Request;

class SectMainController extends Controller
{
    public function get_sect_main_by_religion(Request $request)
    {
        $religionId = $request->religion_id;
        
        if (!$religionId) {
            return response()->json(['error' => 'Religion ID is required'], 400);
        }

        $sectMains = \App\Models\SectMain::where('religion_id', $religionId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'sect_mains' => $sectMains
        ]);
    }
}