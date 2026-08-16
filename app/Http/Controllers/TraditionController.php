<?php

namespace App\Http\Controllers;

use App\Models\Tradition;
use App\Models\SchoolOfThought;
use Illuminate\Http\Request;

class TraditionController extends Controller
{
    public function get_traditions_by_school_of_thought(Request $request)
    {
        $schoolOfThoughtId = $request->school_of_thought_id;
        
        if (!$schoolOfThoughtId) {
            return response()->json(['error' => 'School of thought ID is required'], 400);
        }

        $traditions = \App\Models\Tradition::where('school_of_thought_id', $schoolOfThoughtId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'traditions' => $traditions
        ]);
    }
}