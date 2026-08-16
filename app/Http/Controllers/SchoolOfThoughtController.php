<?php

namespace App\Http\Controllers;

use App\Models\SchoolOfThought;
use App\Models\SectMain;
use Illuminate\Http\Request;

class SchoolOfThoughtController extends Controller
{
    public function get_school_of_thought_by_sect(Request $request)
    {
        $sectMainId = $request->sect_main_id;
        
        if (!$sectMainId) {
            return response()->json(['error' => 'Sect main ID is required'], 400);
        }

        $schoolsOfThought = \App\Models\SchoolOfThought::where('sect_main_id', $sectMainId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'schools_of_thought' => $schoolsOfThought
        ]);
    }
}