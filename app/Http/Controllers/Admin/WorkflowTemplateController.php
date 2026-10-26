<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkflowMessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkflowTemplateController extends Controller
{
    public function index(Request $request)
    {
        $templates=WorkflowMessageTemplate::orderBy('kind')->orderBy('display_order')->orderBy('id')->paginate(40);
        return view('admin.workflow_templates.index',compact('templates'));
    }

    public function save(Request $request, ?WorkflowMessageTemplate $template=null)
    {
        $data=$request->validate([
            'kind'=>['required',Rule::in(['interest','proposal'])],
            'language'=>'required|string|max:12',
            'title'=>'required|string|max:150',
            'opening'=>'required|string|max:2000',
            'body'=>'required|string|max:5000',
            'closing'=>'required|string|max:2000',
            'is_active'=>'required|boolean',
            'display_order'=>'required|integer|min:0|max:9999',
        ]);
        if ($template?->exists) {
            $template->update($data+['updated_by'=>$request->user()->id]);
        } else {
            WorkflowMessageTemplate::create($data+['created_by'=>$request->user()->id,'updated_by'=>$request->user()->id]);
        }
        return redirect()->route('admin.workflow.templates')->with('success','Template saved.');
    }

    public function toggle(Request $request, WorkflowMessageTemplate $template)
    {
        $template->update(['is_active'=>!$template->is_active,'updated_by'=>$request->user()->id]);
        return back()->with('success','Template status updated.');
    }
}
