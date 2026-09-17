<?php

namespace App\Http\Controllers;

use App\Http\Controllers\InstamojoController;
use App\Http\Controllers\PaypalController;
use App\Http\Controllers\PaystackController;
use App\Models\Currency;
use App\Models\ManualPaymentMethod;
use App\Models\PackagePayment;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\DbStoreNotification;
use App\Services\Api\V1\Payment\CustomCoinService;
use App\Services\FirbaseNotification;
use Auth;
use Illuminate\Http\Request;
use Kutia\Larafirebase\Facades\Larafirebase;
use Notification;
use Session;

class WalletController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:wallet_transaction_history'])->only('wallet_transaction_history_admin');
        $this->middleware(['permission:offline_wallet_recharge_requests'])->only('manual_wallet_recharge_requests');
    }

    public function index()
    {
        if (!get_setting('wallet_system')) {
            return back();
        }

        $wallets = Wallet::where('user_id', Auth::user()->id)->latest()->paginate(9);
        $package_payments = PackagePayment::where('user_id', Auth::user()->id)->latest()->paginate(9);

        return view('frontend.member.wallet.index', compact('wallets', 'package_payments'));
    }

    public function wallet_recharge_methods()
    {
        $manual_payments = ManualPaymentMethod::all();
        return view('frontend.member.wallet.recharge_methods', compact('manual_payments'));
    }

    public function show($id)
    {
        $wallet_payment    = Wallet::findOrFail($id);
        return view('admin.wallet.wallet_payment_details', compact('wallet_payment'));
    }

    public function recharge(Request $request)
    {

        // dd($request->all());

        $data['amount'] = $request->amount;
        $data['payment_method'] = $request->payment_option;

        $request->session()->put('payment_type', 'wallet_payment');
        $request->session()->put('payment_data', $data);

        if ($request->payment_option == 'paypal') {
            $paypal = new PaypalController;
            return $paypal->pay();
        } elseif ($request->payment_option == 'instamojo') {
            $instamojo = new InstamojoController;
            return $instamojo->pay($request);
        } elseif ($request->payment_option == 'stripe') {
            $stripe = new StripeController;
            return $stripe->pay();
        } elseif ($request->payment_option == 'razorpay') {
            $razorpay = new RazorpayController;
            return $razorpay->pay($request);
        } elseif ($request->payment_option == 'paystack') {
            $paystack = new PaystackController;
            return $paystack->redirectToGateway($request);
        } elseif ($request->payment_option == 'paytm') {
            $paytm = new PaytmController;
            return $paytm->index();
        } elseif ($request->payment_option == 'aamarpay') {
            $aamarpay = new AamarpayController;
            return $aamarpay->pay();
        } elseif ($request->payment_option == 'sslcommerz') {
            $sslcommerz = new SslcommerzController;
            return $sslcommerz->pay($request);
        } elseif ($request->payment_option == 'phonepe') {
            $phonepe = new PhonepeController;
            return $phonepe->pay($request);
        } elseif ($request->payment_option == 'manual_payment') {
            $user = Auth::user();

            $wallet = new Wallet;
            $wallet->user_id = $user->id;
            $wallet->amount = $request->amount;
            $wallet->payment_method =  ManualPaymentMethod::find($request->manual_payment_id)->heading;
            $wallet->payment_details = $request->payment_details;
            $wallet->offline_payment = 1;
            $wallet->reciept = $request->payment_proof;
            $wallet->transaction_id = $request->transaction_id;
            $wallet->save();

            Session::forget('payment_data');
            Session::forget('payment_type');

            flash(translate('Payment completed'))->success();
            return redirect()->route('wallet.index');
        }
    }

    public function wallet_payment_done($payment_data, $payment_details)
    {
        $user = auth()->user();
        $user->balance = $user->balance + $payment_data['amount'];
        $user->save();

        $wallet = new Wallet;
        $wallet->user_id = $user->id;
        $wallet->amount = $payment_data['amount'];
        $wallet->payment_method = $payment_data['payment_method'];
        $wallet->payment_details = $payment_details;
        $wallet->save();

        Session::forget('payment_data');
        Session::forget('payment_type');

        flash(translate('Payment completed'))->success();
        return redirect()->route('wallet.index');
    }

    public function manual_wallet_recharge_requests()
    {
        $wallets = Wallet::latest()->where('offline_payment', 1)->paginate(10);
        return view('admin.wallet.manual_recharge_requests', compact('wallets'));
    }

    /**
     * Custom coins: purchase form showing the admin-configured per-coin price.
     */
    public function custom_coins_form()
    {
        $unit_price = CustomCoinService::amountFor(1);
        $manual_payments = ManualPaymentMethod::all();

        return view('frontend.member.wallet.custom_coins', compact('unit_price', 'manual_payments'));
    }

    /**
     * Custom coins: start the purchase with the selected payment method.
     * Amount is ALWAYS recalculated server-side from the coin count.
     */
    public function custom_coins_purchase(Request $request)
    {
        $request->validate([
            'coins' => ['required', 'integer', 'min:1', 'max:1000000'],
            'payment_option' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $coins = (int) $request->coins;
        $unit_price = CustomCoinService::amountFor(1);
        $amount = CustomCoinService::amountFor($coins);

        $data = [
            'coins' => $coins,
            'amount' => $amount,
            'payment_method' => $request->payment_option,
        ];

        $request->session()->put('payment_type', 'custom_coins');
        $request->session()->put('payment_data', $data);

        if ($request->payment_option == 'paypal') {
            $paypal = new PaypalController;
            return $paypal->pay();
        } elseif ($request->payment_option == 'instamojo') {
            $instamojo = new InstamojoController;
            return $instamojo->pay($request);
        } elseif ($request->payment_option == 'stripe') {
            $stripe = new StripeController;
            return $stripe->pay();
        } elseif ($request->payment_option == 'razorpay') {
            $razorpay = new RazorpayController;
            return $razorpay->pay($request);
        } elseif ($request->payment_option == 'paystack') {
            $paystack = new PaystackController;
            return $paystack->redirectToGateway($request);
        } elseif ($request->payment_option == 'paytm') {
            $paytm = new PaytmController;
            return $paytm->index();
        } elseif ($request->payment_option == 'aamarpay') {
            $aamarpay = new AamarpayController;
            return $aamarpay->pay();
        } elseif ($request->payment_option == 'sslcommerz') {
            $sslcommerz = new SslcommerzController;
            return $sslcommerz->pay($request);
        } elseif ($request->payment_option == 'phonepe') {
            $phonepe = new PhonepeController;
            return $phonepe->pay($request);
        } elseif (in_array($request->payment_option, ['easypaisa', 'jazzcash'], true)) {
            $request->validate([
                'pakistan_mobile_number' => ['required', 'string', 'max:30'],
                'pakistan_transaction_id' => ['nullable', 'string', 'max:100'],
                'pakistan_payment_note' => ['nullable', 'string', 'max:500'],
            ]);

            $methodName = $request->payment_option === 'easypaisa' ? 'EasyPaisa' : 'JazzCash';
            $payment = new PackagePayment();
            $payment->payment_code = date('ymd-His');
            $payment->user_id = $user->id;
            $payment->package_id = 0;
            $payment->payment_method = $request->payment_option;
            $payment->payment_status = 'Due';
            $payment->amount = $amount;
            $payment->payable_amount = $amount;
            $payment->currency = strtoupper(Currency::find(get_setting('system_default_currency'))->code ?? 'PKR');
            $payment->payment_details = json_encode([
                'mobile_number' => $request->pakistan_mobile_number,
                'transaction_id' => $request->pakistan_transaction_id,
                'note' => $request->pakistan_payment_note,
            ]);
            $payment->offline_payment = 1;
            $payment->custom_payment_name = $methodName;
            $payment->custom_payment_transaction_id = $request->pakistan_transaction_id;
            $payment->custom_payment_details = $request->pakistan_payment_note;
            $payment->metadata = [CustomCoinService::TYPE => [
                'coins' => $coins,
                'unit_price' => $unit_price,
                'amount' => $amount,
            ]];
            $payment->save();

            $this->notifyAdminsAboutPendingPackagePayment($user, $payment);

            Session::forget('payment_data');
            Session::forget('payment_type');

            flash(translate('Payment request submitted. Coins will be added after admin verification.'))->success();
            return redirect()->route('package_payment.invoice', $payment->id);
        } elseif ($request->payment_option == 'manual_payment') {
            $payment = new PackagePayment();
            $payment->payment_code = date('ymd-His');
            $payment->user_id = $user->id;
            $payment->package_id = 0;
            $payment->payment_method = 'manual_payment';
            $payment->payment_status = 'Due';
            $payment->amount = $amount;
            $payment->payable_amount = $amount;
            $payment->currency = strtoupper(Currency::find(get_setting('system_default_currency'))->code ?? 'PKR');
            $payment->payment_details = '';
            $payment->offline_payment = 1;
            $payment->custom_payment_name = ManualPaymentMethod::find($request->manual_payment_id)->heading;
            $payment->custom_payment_transaction_id = $request->transaction_id;
            $payment->custom_payment_proof = $request->payment_proof;
            $payment->custom_payment_details = $request->payment_details;
            $payment->metadata = [CustomCoinService::TYPE => [
                'coins' => $coins,
                'unit_price' => $unit_price,
                'amount' => $amount,
            ]];
            $payment->save();

            $this->notifyAdminsAboutPendingPackagePayment($user, $payment);

            Session::forget('payment_data');
            Session::forget('payment_type');

            flash(translate('Payment request submitted. Coins will be added after admin verification.'))->success();
            return redirect()->route('package_payment.invoice', $payment->id);
        }
    }

    private function notifyAdminsAboutPendingPackagePayment(User $user, PackagePayment $payment): void
    {
        try {
            $notify_type = 'package_purchase';
            $id = unique_notify_id();
            $notify_by = $user->id;
            $info_id = $payment->id;
            $message = $user->first_name . ' ' . $user->last_name . ' ' . translate('submitted a custom coins payment request. Payment Code: ') . $payment->payment_code;
            $route = route('package-payments.index');

            if (get_setting('firebase_push_notification') == 1) {
                $fcmTokens = User::where('user_type', 'admin')
                    ->whereNotNull('fcm_token')
                    ->pluck('fcm_token')
                    ->toArray();
                Larafirebase::withTitle(str_replace("_", " ", $notify_type))
                    ->withBody($message)
                    ->sendMessage($fcmTokens);
            }

            Notification::send(User::where('user_type', 'admin')->first(), new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
        } catch (\Exception $e) {
            // Keep checkout usable even when notification services are not configured.
        }
    }

    /**
     * Custom coins: deliver coins after a successful ONLINE payment
     * (called from the gateway success handlers).
     */
    public function custom_coins_payment_done($payment_data, $payment_details)
    {
        $user = auth()->user();

        $payment = new PackagePayment();
        $payment->payment_code = date('ymd-His');
        $payment->user_id = $user->id;
        $payment->package_id = 0;
        $payment->payment_method = $payment_data['payment_method'];
        $payment->payment_status = 'Paid';
        $payment->amount = $payment_data['amount'];
        $payment->payable_amount = $payment_data['amount'];
        $payment->currency = strtoupper(Currency::find(get_setting('system_default_currency'))->code ?? 'PKR');
        $payment->payment_details = $payment_details;
        $payment->offline_payment = 2;
        $payment->metadata = [CustomCoinService::TYPE => [
            'coins' => (int) $payment_data['coins'],
            'unit_price' => CustomCoinService::amountFor(1),
            'amount' => $payment_data['amount'],
        ]];
        $payment->save();

        // Deliver coins + invoice email (server-side amount re-check inside).
        CustomCoinService::deliverIfPaid($payment);

        Session::forget('payment_data');
        Session::forget('payment_type');

        flash(translate('Coins added to your balance successfully.'))->success();
        return redirect()->route('package_payment.invoice', $payment->id);
    }

    public function wallet_manual_payment_accept($id)
    {
        $wallet = Wallet::findOrFail($id);
        $wallet->approval = 1;
        $user = $wallet->user;
        $user->balance = $user->balance + $wallet->amount;
        $user->save();
        $wallet->save();
        flash(translate('Wallet Manual Payment Accepted Successfully'))->success();
        return redirect()->route('manual_wallet_recharge_requests');
    }

    public function wallet_transaction_history_admin(Request $request)
    {
        $user_id = null;
        $date_range = null;

        if ($request->user_id) {
            $user_id = $request->user_id;
        }

        $users_with_wallet = User::whereIn('id', function ($query) {
            $query->select('user_id')->from(with(new Wallet)->getTable());
        })->get();

        $wallet_history = Wallet::orderBy('created_at', 'desc');

        if ($request->date_range) {
            $date_range = $request->date_range;
            $date_range1 = explode(" / ", $request->date_range);
            $wallet_history = $wallet_history->where('created_at', '>=', $date_range1[0]);
            $wallet_history = $wallet_history->where('created_at', '<=', $date_range1[1]);
        }
        if ($user_id) {
            $wallet_history = $wallet_history->where('user_id', '=', $user_id);
        }

        $wallets = $wallet_history->paginate(10);
        return view('admin.wallet.transaction_history', compact('wallets', 'users_with_wallet', 'user_id', 'date_range'));
    }
}
