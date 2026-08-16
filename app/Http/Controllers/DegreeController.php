<?php

namespace App\Http\Controllers;

use App\Models\Degree;
use App\Models\EducationLevel;
use Illuminate\Http\Request;

class DegreeController extends Controller
{
    public function get_degrees_by_education_level(Request $request)
    {
        $educationLevelId = $request->education_level_id;
        
        $query = \App\Models\Degree::where('is_active', true);

        if ($educationLevelId) {
            $query->where('education_level_id', $educationLevelId);
        }

        $degrees = $query->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'category']);

        return response()->json([
            'success' => true,
            'degrees' => $degrees
        ]);
    }
}