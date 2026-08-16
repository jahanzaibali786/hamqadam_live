<?php

namespace App\Http\Controllers;

use App\Models\FieldOfStudy;
use App\Models\Degree;
use Illuminate\Http\Request;

class FieldOfStudyController extends Controller
{
    public function get_fields_of_study_by_degree(Request $request)
    {
        $degreeId = $request->degree_id;
        
        $query = \App\Models\FieldOfStudy::where('is_active', true);

        if ($degreeId) {
            $query->where('degree_id', $degreeId);
        }

        $fieldsOfStudy = $query->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'fields_of_study' => $fieldsOfStudy
        ]);
    }
}