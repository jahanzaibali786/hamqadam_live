<?php

namespace App\Http\Controllers;

use App\Models\ProfessionCategory;
use App\Models\Profession;
use Illuminate\Http\Request;

class ProfessionController extends Controller
{
    public function get_professions_by_category(Request $request)
    {
        $categoryId = $request->category_id;
        
        if (!$categoryId) {
            return response()->json(['error' => 'Category ID is required'], 400);
        }

        $professions = \App\Models\Profession::where('profession_category_id', $categoryId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'professions' => $professions
        ]);
    }
}