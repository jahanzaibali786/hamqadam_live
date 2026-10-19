<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentCoupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentCouponController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:show_packages');
    }

    public function index(Request $request): View
    {
        $query = PaymentCoupon::query();
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('code', 'like', '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%');
        }
        if (in_array($request->input('status'), ['active', 'disabled', 'expired', 'exhausted'], true)) {
            switch ($request->input('status')) {
                case 'active':
                    $query->where('active', 1)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
                        ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'));
                    break;
                case 'disabled':
                    $query->where('active', 0);
                    break;
                case 'expired':
                    $query->whereNotNull('expires_at')->where('expires_at', '<', now());
                    break;
                case 'exhausted':
                    $query->whereNotNull('usage_limit')->whereColumn('used_count', '>=', 'usage_limit');
                    break;
            }
        }
        return view('admin.payment_coupons.index', [
            'coupons' => $query->latest()->paginate(20)->withQueryString(),
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
        $coupon->update($this->validated($request, (int) $coupon->id));
        flash(translate('Promo code updated successfully.'))->success();
        return back();
    }

    public function destroy(PaymentCoupon $coupon): RedirectResponse
    {
        if ((int) $coupon->used_count > 0) {
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
            'per_user_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'active' => ['nullable', 'boolean'],
        ]);
        $data['code'] = strtoupper(trim($data['code']));
        if ($data['discount_type'] === 'percentage' && (float) $data['discount_value'] > 100) {
            throw ValidationException::withMessages(['discount_value' => 'Percentage discount cannot exceed 100%.']);
        }
        if ($ignoreId !== null && isset($data['usage_limit']) && $data['usage_limit'] !== null) {
            $alreadyUsed = (int) PaymentCoupon::whereKey($ignoreId)->value('used_count');
            if ((int) $data['usage_limit'] < $alreadyUsed) {
                throw ValidationException::withMessages(['usage_limit' => 'Usage limit cannot be lower than existing redemptions.']);
            }
        }
        $data['minimum_amount'] = $data['minimum_amount'] ?? 0;
        $data['active'] = $request->boolean('active');
        return $data;
    }
}
