<?php

namespace App\Http\Controllers;

use App\Models\SupportCategory;
use Illuminate\Http\Request;

class SupportCategoryController extends Controller
{
    public function index()
    {
        return response()->json(SupportCategory::latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $category = SupportCategory::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]));

        flash(translate('New support category has been added successfully'))->success();

        return back()->with('support_category_id', $category->id);
    }

    public function edit(SupportCategory $supportCategory)
    {
        return response()->json($supportCategory);
    }

    public function update(Request $request, SupportCategory $supportCategory)
    {
        $supportCategory->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]));

        flash(translate('Support Category has been updated successfully.'))->success();

        return back();
    }

    public function destroy($id)
    {
        SupportCategory::destroy($id);

        flash(translate('Support Category has been deleted successfully.'))->success();

        return back();
    }
}

