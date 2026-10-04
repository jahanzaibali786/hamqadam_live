<?php

/*
 | The product notification matrix. The NotificationHelper is the only sender;
 | this file keeps copy, channels, critical-event rules, and deep links in one
 | place for both the website and the mobile API.
 */
$events = [];
$add = static function (string $key, string $title, string $message, array $channels, string $route, bool $critical = false) use (&$events): void {
    $events[$key] = compact('title', 'message', 'channels', 'route', 'critical');
};

$add('account_created', 'Welcome to Hamqadam', 'Your Hamqadam account has been successfully created. May your search be blessed.', ['push', 'email'], '/dashboard', true);
$add('welcome_plan_activated', 'Your Hamqadam Welcome Gift Is Here', 'Your :plan plan has been activated as your welcome reward. You have received :coins Coins, :views Profile Views, and :interests Interest/Proposal Requests with :acceptances Acceptances. Start discovering meaningful connections today.', ['push', 'email'], '/packages', true);
$add('email_otp', 'Verify Your Journey', 'Your verification code is :otp. One small step toward finding the right connection.', ['push', 'email'], '/verify-email', true);
$add('otp_resent', 'Your Code Is Ready', 'A new verification code has been sent to your email.', ['push', 'email'], '/verify-email', true);
$add('password_reset_requested', 'Let’s Get You Back', 'A password reset request was received for your account.', ['push', 'email'], '/password/reset', true);
$add('password_changed', 'Password Updated', 'Your password has been changed successfully. Your journey remains secure.', ['push', 'email'], '/login', true);
$add('new_login', 'New Login Detected', 'Your account was accessed from a new login. Please make sure it was you.', ['push', 'email'], '/account/security', true);
$add('new_device_login', 'New Device Connected', 'Your Hamqadam account was accessed from a new device.', ['push', 'email'], '/account/security', true);
$add('suspicious_login', 'Your Security Matters', 'We noticed unusual activity on your account. Please review it to keep your journey safe.', ['push', 'email'], '/account/security', true);
$add('failed_login_attempts', 'Multiple Login Attempts Detected', 'We noticed several unsuccessful login attempts on your account. Please review your account security.', ['push', 'email'], '/account/security', true);
$add('account_locked', 'Account Temporarily Locked', 'Your account has been temporarily protected for security reasons.', ['push', 'email'], '/account/security', true);
$add('account_status_updated', 'Account Status Updated', 'Your Hamqadam account status has been updated successfully.', ['push', 'email'], '/account/security', true);
$add('security_announcement', 'Important Security Update', 'There is an important security update regarding your Hamqadam account.', ['push', 'email'], '/account/security', true);

$add('profile_created', 'Your Story Has Begun', 'Your Hamqadam profile is ready. Your journey toward a meaningful connection can begin.', ['push', 'email'], '/profile-settings');
$add('verification_submitted', 'Your Verification Is Underway', 'Your verification request has been submitted successfully. We will take it from here.', ['push', 'email'], '/verification');
$add('verification_review', 'Verification in Progress', 'Your profile verification is currently being reviewed. We will let you know when it is complete.', ['push', 'email'], '/verification');
$add('verification_approved', 'You’re Verified', 'Your Hamqadam profile has been verified. A trusted journey begins with trust.', ['push', 'email'], '/dashboard');
$add('verification_rejected', 'A Little More Needed', 'Your profile verification could not be approved. Please review the details and try again.', ['push', 'email'], '/profile-settings');
$add('profile_correction_required', 'Let’s Perfect Your Story', 'A few profile details need your attention before your profile can be approved.', ['push', 'email'], '/profile-settings');
$add('photo_approved', 'Your Photo Is Approved', 'Your profile photo has been approved and is ready to make a meaningful first impression.', ['push'], '/profile-settings');
$add('photo_rejected', 'Let’s Find the Right Picture', 'Your profile photo could not be approved. Please upload a photo that follows our guidelines.', ['push', 'email'], '/profile-settings');
$add('identity_approved', 'Identity Verified', 'Your identity verification has been successfully completed.', ['push', 'email'], '/dashboard');
$add('reverification_required', 'Verification Needs Your Attention', 'We need you to complete verification again to keep your profile trusted and secure.', ['push', 'email'], '/verification');

$add('new_top_match', 'A New Match Worth Discovering', 'Our AI found :count new profiles among your Top 5 matches based on your preferences.', ['push', 'email'], '/ai-matches');
$add('top_matches_updated', 'Your Top Matches Have Changed', 'Your AI-powered Top 5 matches have been updated with new possibilities.', ['push'], '/ai-matches');
$add('ai_suggested_profile', 'Someone May Be Worth Discovering', 'Our AI found a profile that may align with what you are looking for.', ['push'], '/ai-matches');
$add('preference_match', 'A New Possibility', 'We found a new profile that matches your preferences. Some stories are worth discovering.', ['push'], '/ai-matches');
$add('daily_ai_matches', 'Your Daily Matches Are Ready', 'We found new possibilities based on your preferences. Take a moment to discover them.', ['push', 'email_optional'], '/ai-matches');
$add('profile_viewed', '', '', [], '/profile-views');
$add('profile_shortlisted', '', '', [], '/shortlists');
$add('relevant_profile_recommendation', 'New Possibilities for You', 'We found new profiles that may be relevant to what you are looking for.', ['push', 'email_optional'], '/members');

$add('interest_received', 'Someone Is Interested', ':name has expressed interest in you.', ['push', 'email'], '/interests');
$add('interest_accepted', 'Your Interest Was Accepted', ':name accepted your interest. One step closer to something meaningful.', ['push', 'email'], '/interests');
$add('interest_declined', 'Not Every Story Aligns', ':name declined your interest. Keep your heart open to the possibilities ahead.', ['push'], '/members');
$add('mutual_interest', 'It’s a Match', 'You and :name both showed interest. Sometimes two stories simply find each other.', ['push', 'email'], '/matches');
$add('match_created', 'A New Connection Begins', 'You have a new match with :name on Hamqadam. Take the next step when you are ready.', ['push', 'email'], '/matches');
$add('match_reminder', 'A Connection Is Waiting', 'You have a meaningful connection with :name waiting for your attention.', ['push', 'email_optional'], '/matches');
$add('got_match', 'A Meaningful Match', 'You confirmed a successful match with :name. Every beautiful journey begins with a connection.', ['push'], '/matches');
$add('match_success', 'Your Story Found a Connection', 'Congratulations on your successful match with :name. May this be the beginning of something beautiful.', ['push', 'email'], '/matches');

$add('proposal_received', 'A New Proposal', 'You have received a proposal from :name. Take a moment to discover their story.', ['push', 'email'], '/proposals');
$add('proposal_accepted', 'A New Chapter Begins', ':name has accepted your proposal. May this connection grow into something meaningful.', ['push', 'email'], '/proposals');
$add('proposal_declined', 'Every Story Has Its Path', ':name has declined your proposal. Keep exploring meaningful possibilities.', ['push'], '/members');
$add('proposal_expiring', 'Your Proposal Is Waiting', 'A proposal from :name is approaching its response deadline.', ['push', 'email'], '/proposals');
$add('proposal_expired', 'Proposal Expired', 'Your proposal to :name has expired. New possibilities are still waiting.', ['push'], '/members');
$add('proposal_pending_reminder', 'A Decision Is Waiting', 'You have a pending proposal from :name that needs your attention.', ['push', 'email'], '/proposals');
$add('proposal_shared_guardian', 'A Story Shared with Family', 'A proposal from :name has been shared with your Family/Guardian.', ['push', 'email'], '/family');
$add('guardian_proposal_response', 'Your Family Has Responded', 'Your Family/Guardian has responded to the proposal from :name.', ['push', 'email'], '/family');

$add('chat_message', 'A Message for You', ':name sent you a message. Every meaningful connection starts with a conversation.', ['push'], '/chat');
$add('message_request', 'Someone Wants to Connect', ':name has sent you a message request.', ['push', 'email'], '/chat');
$add('message_request_accepted', 'The Conversation Can Begin', ':name accepted your message request. Your conversation starts here.', ['push'], '/chat');
$add('missed_audio_call', 'You Missed a Call', ':name tried to reach you.', ['push'], '/calls');
$add('missed_video_call', 'You Missed a Video Call', ':name tried to connect with you.', ['push'], '/calls');
$add('unread_conversation', 'Your Conversation Is Waiting', 'You have an unread conversation waiting for you.', ['push', 'email_optional'], '/chat');

$add('guardian_invitation', 'Family Involvement Begins', 'You have received a Family/Guardian invitation.', ['push', 'email'], '/family');
$add('guardian_invitation_accepted', 'Family Is Now Part of the Journey', ':name accepted the Family/Guardian invitation.', ['push'], '/family');
$add('guardian_invitation_declined', 'Guardian Invitation Declined', ':name declined the Family/Guardian invitation.', ['push'], '/family');
$add('guardian_action_required', 'Your Family’s Input Matters', 'A Family/Guardian action is required.', ['push', 'email'], '/family');
$add('guardian_response_received', 'Your Family Has Responded', 'Your Family/Guardian has responded to your request.', ['push', 'email'], '/family');

$add('subscription_activated', 'Your Premium Journey Begins', 'Your Hamqadam subscription has been activated successfully. Enjoy the journey.', ['push', 'email'], '/packages', true);
$add('subscription_renewal_reminder', 'Your Journey Continues', 'Your subscription will renew soon.', ['push', 'email'], '/packages', true);
$add('subscription_renewed', 'Your Premium Journey Continues', 'Your Hamqadam subscription has been renewed successfully.', ['push', 'email'], '/packages', true);
$add('subscription_expiring', 'Your Premium Journey Is Almost Due', 'Your subscription will expire soon. Renew to continue enjoying premium features.', ['push', 'email'], '/packages', true);
$add('subscription_expired', 'Your Premium Journey Has Ended', 'Your subscription has expired. You can continue your journey or choose a new plan.', ['push', 'email'], '/packages', true);
$add('payment_successful', 'Payment Complete', 'Your payment was successful. Your selected experience is now ready.', ['push', 'email'], '/packages', true);
$add('payment_failed', 'Payment Needs Your Attention', 'Your payment could not be completed. Please try again to continue your journey.', ['push', 'email'], '/packages', true);
$add('payment_retry', 'One More Step', 'Your payment requires another attempt to complete your subscription.', ['push', 'email'], '/packages', true);
$add('refund_processed', 'Refund Complete', 'Your refund has been processed successfully.', ['push', 'email'], '/wallet', true);
$add('plan_upgraded', 'Your Experience Just Got Better', 'Your plan has been upgraded successfully. Explore what is now available to you.', ['push', 'email'], '/packages', true);
$add('plan_downgraded', 'Plan Updated', 'Your plan has been downgraded successfully.', ['push', 'email'], '/packages', true);
$add('subscription_cancelled', 'Subscription Cancelled', 'Your subscription has been cancelled successfully. Your account remains yours.', ['push', 'email'], '/packages', true);

$add('referral_registered', 'Your Referral Joined Hamqadam', 'Your referral has successfully joined Hamqadam.', ['push'], '/referrals');
$add('referral_reward', 'Your Referral Reward Is Here', 'You earned :coins Coins from your referral.', ['push'], '/rewards');
$add('referral_milestone', 'Milestone Reached', 'Congratulations! You reached a referral milestone.', ['push'], '/rewards');
$add('achievement_unlocked', 'Achievement Unlocked', 'Congratulations! You reached a new milestone on Hamqadam.', ['push', 'email_optional'], '/rewards');
$add('reward_expiring', 'Your Reward Is Waiting', 'Your reward is expiring soon. Make the most of it.', ['push', 'email_optional'], '/rewards');

$add('support_ticket_created', 'We’ve Got You', 'Your support ticket has been created successfully. We’ll help you from here.', ['push', 'email'], '/support-ticket/history');
$add('support_agent_replied', 'Your Support Agent Replied', 'We have a response waiting for you.', ['push', 'email'], '/support-ticket/history');
$add('support_ticket_updated', 'Your Ticket Has an Update', 'The status or expected resolution time of your support ticket has been updated.', ['push', 'email'], '/support-ticket/history');
$add('support_ticket_resolved', 'We’ve Resolved Your Issue', 'Your support ticket has been resolved. We hope we made your journey easier.', ['push', 'email'], '/support-ticket/history');
$add('support_rating_request', 'How Was Your Experience?', 'Your feedback helps us make Hamqadam better.', ['push'], '/support-ticket/history');
$add('safety_action_required', 'Your Safety Matters', 'A safety action is required on your account.', ['push', 'email'], '/account/security', true);

return ['events' => $events];
