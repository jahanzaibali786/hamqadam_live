<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentCoupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentCouponController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:show_packages');
    }

    public function index(): View
    {
        return view('admin.payment_coupons.index', [
            'coupons' => PaymentCoupon::latest()->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        PaymentCoupon::create($this->validated($request));
        flash(translate('Promo code created successfully.'))->success();

        return back();
    }

    public function update(Request $request, PaymentCoupon $coupon): RedirectResponse
    {
        $coupon->update($this->validated($request, $coupon->id));
        flash(translate('Promo code updated successfully.'))->success();

        return back();
    }

    public function destroy(PaymentCoupon $coupon): RedirectResponse
    {
        if ($coupon->used_count > 0) {
            $coupon->update(['active' => false]);
            flash(translate('Used promo codes are retained for audit and have been disabled.'))->warning();
        } else {
            $coupon->delete();
            flash(translate('Promo code deleted successfully.'))->success();
        }

        return back();
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $unique = 'unique:payment_coupons,code' . ($ignoreId ? ',' . $ignoreId : '');
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', $unique],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'minimum_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'active' => ['nullable', 'boolean'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['minimum_amount'] = $data['minimum_amount'] ?? 0;
        $data['active'] = $request->boolean('active');

        return $data;
    }
}
