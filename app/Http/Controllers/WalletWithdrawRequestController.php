<?php

namespace App\Http\Controllers;

use App\Models\WalletWithdrawRequest;
use Illuminate\Http\Request;

class WalletWithdrawRequestController extends Controller
{
    public function index()
    {
        $withdrawRequests = WalletWithdrawRequest::latest()->paginate(20);

        return view()->exists('admin.wallet.manual_recharge_requests')
            ? view('admin.wallet.manual_recharge_requests', ['wallets' => $withdrawRequests])
            : response()->json($withdrawRequests);
    }

    public function wallet_withdraw_request_history()
    {
        $withdrawRequests = WalletWithdrawRequest::where('user_id', auth()->id())->latest()->paginate(20);

        return response()->json($withdrawRequests);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'details' => ['nullable', 'string'],
        ]);

        $withdrawRequest = WalletWithdrawRequest::create($data + [
            'user_id' => auth()->id(),
            'status' => 0,
        ]);

        flash(translate('Withdraw request has been submitted successfully'))->success();

        return back()->with('withdraw_request_id', $withdrawRequest->id);
    }

    public function wallet_withdraw_request_details(Request $request)
    {
        $withdrawRequest = WalletWithdrawRequest::findOrFail($request->id);

        return response()->json($withdrawRequest);
    }

    public function withdraw_request_accept($id)
    {
        $withdrawRequest = WalletWithdrawRequest::findOrFail($id);
        $withdrawRequest->status = 1;
        $withdrawRequest->save();

        flash(translate('Withdraw request accepted'))->success();

        return back();
    }

    public function withdraw_request_reject($id)
    {
        $withdrawRequest = WalletWithdrawRequest::findOrFail($id);
        $withdrawRequest->status = 2;
        $withdrawRequest->save();

        flash(translate('Withdraw request rejected'))->success();

        return back();
    }
}

