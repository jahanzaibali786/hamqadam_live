<?php

namespace App\Http\Controllers;

use App\Models\ExpertQuestion;
use Illuminate\Http\Request;

class MemberExpertQuestionController extends Controller
{
    public function index(Request $request)
    {
        $questions=ExpertQuestion::where('user_id',$request->user()->id)->latest()->paginate(15);
        return view('frontend.member.ask_expert',compact('questions'));
    }

    public function store(Request $request)
    {
        $data=$request->validate([
            'category'=>'required|string|in:relationship,marriage,family,communication,other',
            'question'=>'required|string|min:10|max:255',
            'details'=>'nullable|string|max:3000',
            'is_anonymous'=>'nullable|boolean',
        ]);
        ExpertQuestion::create([
            'user_id'=>$request->user()->id,
            'category'=>$data['category'],
            'question'=>$data['question'],
            'details'=>$data['details']??null,
            'is_anonymous'=>$request->boolean('is_anonymous'),
            'status'=>'pending',
        ]);
        return redirect()->route('member.expert.index')->with('success','Your question has been submitted to our experts.');
    }
}
