<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdvisorConsultationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdvisorConsultationController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(AdvisorConsultationRequest::STATUSES)],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $status = (string) ($validated['status'] ?? '');
        $requests = AdvisorConsultationRequest::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $pattern = '%' . addcslashes($search, '%_\\') . '%';
                $query->where(fn ($q) => $q->where('name', 'like', $pattern)
                    ->orWhere('email', 'like', $pattern)
                    ->orWhere('phone', 'like', $pattern)
                    ->orWhere('advisor', 'like', $pattern));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = AdvisorConsultationRequest::selectRaw('status, count(*) as total')
            ->groupBy('status')->pluck('total', 'status');

        return view('admin.advisor_consultations.index', compact('requests', 'counts', 'search', 'status'));
    }

    public function show(AdvisorConsultationRequest $consultation): View
    {
        return view('admin.advisor_consultations.show', compact('consultation'));
    }

    public function update(Request $request, AdvisorConsultationRequest $consultation): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(AdvisorConsultationRequest::STATUSES)],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $consultation->update($validated);
        return redirect()->route('admin.advisor_consultations.show', $consultation)
            ->with('success', 'Advisor consultation updated.');
    }
}
