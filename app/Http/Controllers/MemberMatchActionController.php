<?php

namespace App\Http\Controllers;

use App\Models\ProfileMatch;
use Illuminate\Http\Request;

class MemberMatchActionController extends Controller
{
    /** Legacy one-click submit now requires member preview and consent. */
    public function propose(Request $request, int $member)
    {
        ProfileMatch::query()->visibleToMember($request->user())->where('match_id',$member)->firstOrFail();

        return redirect()->route('member.workflow.show',$member);
    }
}
