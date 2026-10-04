<?php
namespace App\Utility;
use Notification;
use App\Notifications\EmailNotification;
use App\Models\Package;
use App\Models\User;
use Auth;
use Illuminate\Support\Facades\Log;

class EmailUtility
{
    public static function account_oppening_email($user_id = '', $pass = '')
    {
        $user           = User::where('id',$user_id)->first();
        $subject        = get_email_template('account_oppening_email','subject');
        $account_type   = $user->membership == 0 ? 'Basic' : null;
        $email_body     = get_email_template('account_oppening_email','body');
        $email_body     = str_replace('[[name]]', $user->first_name.' '.$user->last_name, $email_body);
        $email_body     = str_replace('[[sitename]]', get_setting('website_name'), $email_body);
        $email_body     = str_replace('[[account_type]]', $account_type, $email_body);
        $email_body     = str_replace('[[email]]', $user->email, $email_body);
        $email_body     = str_replace('[[password]]', $pass, $email_body);
        $email_body     = str_replace('[[url]]', env('APP_URL'), $email_body);
        $email_body     = str_replace('[[from]]', env('MAIL_FROM_NAME'), $email_body);

        try{
            Notification::send($user, new EmailNotification($subject, $email_body));
        }
        catch(\Exception $e){
            // dd($e);
        }
    }

    public static function account_opening_email_to_admin($user = '', $admin = '')
    {
        $subject = get_email_template('account_opening_email_to_admin','subject');
        $email_body = get_email_template('account_opening_email_to_admin','body');
        $email_body = str_replace('[[member_name]]', $user->first_name.' '.$user->last_name, $email_body);
        $email_body = str_replace('[[email]]', $user->email, $email_body);
        $email_body = str_replace('[[profile_link]]', env('APP_URL').'/admin/members/'.$user->id, $email_body);
        $email_body = str_replace('[[from]]', env('MAIL_FROM_NAME'), $email_body);

        try{
            Notification::send($admin, new EmailNotification($subject, $email_body));
        }
        catch(\Exception $e){
            // dd($e);
        }
    }

    public static function member_verification_email($user = '', $status)
    {
        $subject = get_email_template('member_verification_email','subject');
        $email_body = get_email_template('member_verification_email','body');
        $email_body = str_replace('[[name]]', $user->first_name.' '.$user->last_name, $email_body);
        $email_body = str_replace('[[status]]', $status, $email_body);
        $email_body = str_replace('[[from]]', env('MAIL_FROM_NAME'), $email_body);

        try{
            Notification::send($user, new EmailNotification($subject, $email_body));
        }
        catch(\Exception $e){
            // dd($e);
        }
    }

    public static function staff_account_opening_email($user = '', $pass = '', $role_name = '')
    {
        $subject    = get_email_template('staff_account_opening_email','subject');
        $email_body = get_email_template('staff_account_opening_email','body');
        $email_body = str_replace('[[name]]', $user->first_name.' '.$user->last_name, $email_body);
        $email_body = str_replace('[[site_name]]', get_setting('website_name'), $email_body);
        $email_body = str_replace('[[role_type]]', $role_name, $email_body);
        $email_body = str_replace('[[email]]', $user->email, $email_body);
        $email_body = str_replace('[[password]]', $pass, $email_body);
        $email_body = str_replace('[[url]]', env('APP_URL'), $email_body);
        $email_body = str_replace('[[from]]', env('MAIL_FROM_NAME'), $email_body);
        
        try{
            Notification::send($user, new EmailNotification($subject, $email_body));
        }
        catch(\Exception $e){
            // dd($e);
        }
    }

    public static function package_purchase_email($user = '', $package_payment = '')
    {
        $account_type = $package_payment->package_id== 1 ? 'Free' : 'Preminum';
        $package_name = Package::where('id',$package_payment->package_id)->first()->name;
        $subject    = get_email_template('package_purchase_email','subject');
        $email_body = get_email_template('package_purchase_email','body');
        $email_body = str_replace('[[name]]', $user->first_name.' '.$user->last_name, $email_body);
        $email_body = str_replace('[[site_name]]', get_setting('website_name'), $email_body);
        $email_body = str_replace('[[account_type]]', $account_type , $email_body);
        $email_body = str_replace('[[payment_code]]', $package_payment->payment_code, $email_body);
        $email_body = str_replace('[[package]]', $package_name, $email_body);
        $email_body = str_replace('[[amount]]', $package_payment->amount, $email_body);
        $email_body = str_replace('[[from]]', env('MAIL_FROM_NAME'), $email_body);

        try{
            Notification::send($user, new EmailNotification($subject, $email_body));
        }
        catch(\Exception $e){
            // dd($e);
        }
    }

    /** Invoice email for a custom-coin purchase — same template as package purchase. */
    public static function custom_coin_purchase_email($user = '', $package_payment = '', $coins = 0)
    {
        $subject    = get_email_template('package_purchase_email','subject');
        $email_body = get_email_template('package_purchase_email','body');
        $email_body = str_replace('[[name]]', $user->first_name.' '.$user->last_name, $email_body);
        $email_body = str_replace('[[site_name]]', get_setting('website_name'), $email_body);
        $email_body = str_replace('[[account_type]]', 'Preminum', $email_body);
        $email_body = str_replace('[[payment_code]]', $package_payment->payment_code, $email_body);
        $email_body = str_replace('[[package]]', $coins.' '.__('Custom Coins'), $email_body);
        $email_body = str_replace('[[amount]]', $package_payment->amount, $email_body);
        $email_body = str_replace('[[from]]', env('MAIL_FROM_NAME'), $email_body);

        try{
            Notification::send($user, new EmailNotification($subject, $email_body));
        }
        catch(\Exception $e){
            // dd($e);
        }
    }

    public static function manual_payment_approval_email($user = '', $package_payment = '')
    {
        $account_type = $package_payment->package_id== 1 ? 'Free' : 'Preminum';
        $package_name = Package::where('id',$package_payment->package_id)->first()->name;
        $subject    = get_email_template('manual_payment_approval_email','subject');
        $email_body = get_email_template('manual_payment_approval_email','body');
        $email_body = str_replace('[[name]]', $user->first_name.' '.$user->last_name, $email_body);
        $email_body = str_replace('[[account_type]]', $account_type , $email_body);
        $email_body = str_replace('[[payment_code]]', $package_payment->payment_code, $email_body);
        $email_body = str_replace('[[package]]', $package_name, $email_body);
        $email_body = str_replace('[[amount]]', $package_payment->amount, $email_body);
        $email_body = str_replace('[[from]]', env('MAIL_FROM_NAME'), $email_body);

        try{
            Notification::send($user, new EmailNotification($subject, $email_body));
        }
        catch(\Exception $e){
            // dd($e);
        }
    }

    public static function email_on_accepting_interest($user = '', $interest = '')
    {
        $subject    = get_email_template('email_on_accepting_interest','subject');
        $email_body = get_email_template('email_on_accepting_interest','body');
        $email_body = str_replace('[[name]]', $user->first_name.' '.$user->last_name, $email_body);
        $email_body = str_replace('[[member_name]]', $interest->user->first_name.' '.$interest->user->last_name , $email_body);
        $email_body = str_replace('[[from]]', env('MAIL_FROM_NAME'), $email_body);

        try{
            Notification::send($user, new EmailNotification($subject, $email_body));
        }
        catch(\Exception $e){
            // dd($e);
        }
    }

    public static function password_reset_email($user = '', $code = '')
    {
        $subject    = get_email_template('password_reset_email','subject');
        $email_body = get_email_template('password_reset_email','body');
        $email_body = str_replace('[[name]]', $user->first_name.' '.$user->last_name, $email_body);
        $email_body = str_replace('[[code]]', $code, $email_body);
        $email_body = str_replace('[[from]]', env('MAIL_FROM_NAME'), $email_body);

        try{
            Notification::send($user, new EmailNotification($subject, $email_body));
        }
        catch(\Exception $e){
            // dd($e);
        }
    }

    public static function email_on_request($user, $identifier)
    {   
        $auth_user  = Auth::user();
        $subject    = get_email_template($identifier,'subject');
        $email_body = get_email_template($identifier,'body');
        $email_body = str_replace('[[name]]', $user->first_name.' '.$user->last_name, $email_body);
        $email_body = str_replace('[[member_name]]', $auth_user->first_name.' '.$auth_user->last_name , $email_body);
        $email_body = str_replace('[[from]]', env('MAIL_FROM_NAME'), $email_body);
        try{
            Notification::send($user, new EmailNotification($subject, $email_body));
        }
        catch(\Exception $e){
            // dd($e);
        }
    }

    public static function email_on_accept_request($notify_user, $identifier)
    {
        $auth_user  = Auth::user();
        $subject    = get_email_template($identifier,'subject');
        $email_body = get_email_template($identifier,'body');
        $email_body = str_replace('[[name]]', $notify_user->first_name.' '.$notify_user->last_name, $email_body);
        $email_body = str_replace('[[member_name]]', $auth_user->first_name.' '. $auth_user->last_name , $email_body);
        $email_body = str_replace('[[from]]', env('MAIL_FROM_NAME'), $email_body);
        try{
            Notification::send($notify_user, new EmailNotification($subject, $email_body));
        }
        catch(\Exception $e){
            // dd($e);
        }
    }


    // Email verification for user Registration
    public static function email_verification_for_registration_user($identifier, $email, $verificationCode): bool
    {
        $siteName = get_setting('website_name') ?: config('app.name');
        $fromName = env('MAIL_FROM_NAME', $siteName);
        $emailSubject = get_email_template($identifier, 'subject');
        $subject = $emailSubject ?: '[[site_name]] Email Verification Code';
        $subject = str_replace('[[site_name]]', $siteName, $subject);

        $email_body = get_email_template($identifier, 'body');
        if (empty($email_body)) {
            $email_body = '<p>Assalam o Alaikum,</p>'
                . '<p>Your verification code for [[site_name]] is:</p>'
                . '<p style="font-size: 28px; font-weight: 700; letter-spacing: 6px; color: #e91e63; margin: 24px 0;">[[code]]</p>'
                . '<p>Please enter this code to complete your Hamqadam registration.</p>'
                . '<p>Regards,<br>[[from]]</p>';
        }

        $email_body = str_replace('[[code]]', $verificationCode, $email_body);
        $email_body = str_replace('[[site_name]]', $siteName, $email_body);
        $email_body = str_replace('[[from]]', $fromName, $email_body);

        try {
            Notification::route('mail', $email)->notify(new EmailNotification($subject, $email_body));
            return true;
        } catch (\Throwable $e) {
            Log::warning('Registration email verification OTP failed to send.', [
                'email' => $email,
                'message' => $e->getMessage(),
            ]);
        }

        return false;
    }


    // Guardian invitation email with token and panel access instructions
    public static function guardian_invitation_email($inviter, string $email, string $relationship, string $token, string $guardianRole = '', bool $isWali = false): bool
    {
        $siteName = get_setting('website_name') ?: config('app.name', 'Hamqadam');
        $fromName = env('MAIL_FROM_NAME', $siteName);
        $roleLabel = $isWali ? 'Wali (Guardian)' : ($guardianRole ?: $relationship);
        $inviterName = trim(($inviter->first_name ?? '') . ' ' . ($inviter->last_name ?? '')) ?: 'A member';
        $acceptUrl = url('/guardian-mode/accept/' . $token);

        $subject = get_email_template('guardian_invitation_email', 'subject');
        if (empty($subject)) {
            $subject = '[[inviter_name]] invited you as a [[role]] on [[site_name]]';
        }
        $subject = str_replace(['[[inviter_name]]', '[[role]]', '[[site_name]]'], [$inviterName, $roleLabel, $siteName], $subject);

        $email_body = get_email_template('guardian_invitation_email', 'body');
        if (empty($email_body)) {
            $email_body = '<div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">'
                . '<div style="background: linear-gradient(135deg, #1b365d, #0b1e36); color: #ffffff; padding: 28px 24px; text-align: center;">'
                . '<h1 style="margin: 0; font-size: 24px; font-weight: 700; letter-spacing: 0.5px; color: #ffffff;">' . e($siteName) . '</h1>'
                . '<p style="margin: 6px 0 0 0; font-size: 13.5px; color: #cbd5e1; letter-spacing: 1px; text-transform: uppercase;">' . __('Family & Guardian Portal') . '</p>'
                . '</div>'
                . '<div style="padding: 32px 28px; color: #334155; font-size: 15px; line-height: 1.65;">'
                . '<p style="margin-top: 0; font-size: 17px; font-weight: 600; color: #0f172a;">Assalam-o-Alaikum,</p>'
                . '<p><strong>' . e($inviterName) . '</strong> has invited you to join <strong>' . e($siteName) . '</strong> as their trusted <strong>' . e($roleLabel) . '</strong>.</p>'
                . '<div style="background: #f8fafc; border-left: 4px solid #1b365d; border-radius: 8px; padding: 16px 20px; margin: 22px 0;">'
                . '<h4 style="margin: 0 0 8px 0; color: #1b365d; font-size: 15px;">' . __('About Guardian & Wali Access:') . '</h4>'
                . '<p style="margin: 0; font-size: 13.5px; color: #475569;">' . __('Guardian Mode allows family members to actively assist and guide their loved ones in finding a compatible life partner. You can review recommended profiles, shortlist promising matches, leave feedback, and participate in family introductions with complete privacy.') . '</p>'
                . '</div>'
                . '<h4 style="margin: 24px 0 10px 0; color: #0f172a; font-size: 15px;">' . __('How to Access Your Guardian Panel:') . '</h4>'
                . '<ol style="padding-left: 20px; margin: 0 0 24px 0; font-size: 14px; color: #475569; line-height: 1.8;">'
                . '<li>' . __('Click the button below to automatically accept your invitation.') . '</li>'
                . '<li>' . __('Log in with your existing account or create a free account with your email:') . ' <strong>' . e($email) . '</strong></li>'
                . '<li>' . __('Once logged in, your Guardian Panel will open at') . ' <code>' . url('/guardian-panel') . '</code> ' . __('where you can review matches.') . '</li>'
                . '</ol>'
                . '<div style="text-align: center; margin: 30px 0;">'
                . '<a href="' . $acceptUrl . '" style="display: inline-block; background: #1b365d; color: #ffffff !important; text-decoration: none; padding: 14px 34px; border-radius: 8px; font-weight: 600; font-size: 15px; box-shadow: 0 4px 12px rgba(27,54,93,0.3);">' . __('Accept Invitation & Open Guardian Panel') . '</a>'
                . '</div>'
                . '<div style="background: #f1f5f9; border-radius: 8px; padding: 14px; text-align: center; margin-bottom: 20px;">'
                . '<span style="font-size: 12.5px; color: #64748b; display: block; margin-bottom: 4px;">' . __('Or enter this invitation code on the Guardian Panel:') . '</span>'
                . '<strong style="font-size: 15px; color: #0f172a; letter-spacing: 1px; font-family: monospace;">' . e($token) . '</strong>'
                . '</div>'
                . '<p style="font-size: 12px; color: #94a3b8; text-align: center; word-break: break-all; margin: 0;">' . __('Direct link:') . ' <a href="' . $acceptUrl . '" style="color: #1b365d;">' . $acceptUrl . '</a></p>'
                . '<hr style="border: none; border-top: 1px solid #e2e8f0; margin: 26px 0;">'
                . '<p style="margin-bottom: 0; font-size: 13px; color: #64748b;">' . __('Warm regards,') . '<br><strong style="color: #0f172a;">' . e($fromName) . '</strong></p>'
                . '</div>'
                . '</div>';
        } else {
            $email_body = str_replace(
                ['[[inviter_name]]', '[[role]]', '[[token]]', '[[accept_url]]', '[[site_name]]', '[[from]]'],
                [$inviterName, $roleLabel, $token, $acceptUrl, $siteName, $fromName],
                $email_body
            );
        }

        try {
            Notification::route('mail', $email)->notify(new EmailNotification($subject, $email_body));
            return true;
        } catch (\Throwable $e) {
            Log::warning('Guardian invitation email failed to send.', [
                'email' => $email,
                'message' => $e->getMessage(),
            ]);
        }

        return false;
    }

}

