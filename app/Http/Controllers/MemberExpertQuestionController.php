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
    public function conversation(Request $request,int $question)
    {
        $question=ExpertQuestion::where('user_id',$request->user()->id)->findOrFail($question);
        $messages=\App\Models\ExpertQuestionMessage::where('expert_question_id',$question->id)->oldest()->paginate(30);
        return view('frontend.member.expert_conversation',compact('question','messages'));
    }

    public function reply(Request $request,int $question)
    {
        $entry=ExpertQuestion::where('user_id',$request->user()->id)->findOrFail($question);
        $data=$request->validate(['body'=>['required','string','max:5000']]);
        \App\Models\ExpertQuestionMessage::create(['expert_question_id'=>$entry->id,'sender_id'=>$request->user()->id,'body'=>$data['body']]);
        if($entry->status==='answered')$entry->update(['status'=>'pending']);
        return back()->with('success','Message sent.');
    }
}

