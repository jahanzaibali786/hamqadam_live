<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\ContactUs;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use App\Notifications\EmailNotification;

/**
 * Guest (no-login) Help Center submission — the form a visitor fills when
 * they press Help before creating an account: name, email, description.
 *
 * It lands in the SAME `contact_us` table the website's Contact Us page
 * writes to, so the admin panel's existing "Contact Us Queries" list shows
 * these tickets with nothing new to learn. No auth, no coin, no thread:
 * once the visitor signs up, the full Help Center chat takes over.
 */
class PublicHelpController extends ApiController
{
    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = ContactUs::create([
            'name' => trim($validated['name']),
            'email' => trim($validated['email']),
            'category' => 'issue',
            'subject' => 'Help Center (guest)',
            'description' => trim($validated['description']),
        ]);

        // Same admin email alert the website contact form sends — best effort.
        try {
            $admin = User::where('user_type', 'admin')->first();
            if ($admin) {
                Notification::send(
                    $admin,
                    new EmailNotification(
                        'Help Center request from ' . $ticket->name,
                        $ticket->description . "\n\nReply to: " . $ticket->email,
                    ),
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Guest help email alert failed.', ['error' => $e->getMessage()]);
        }

        return $this->success(
            ['id' => $ticket->id],
            'Your request has been sent to our Help Center. We will reply on your email.',
            201,
        );
    }
}
