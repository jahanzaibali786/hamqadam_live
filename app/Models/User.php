<?php

namespace App\Models;
use App\Models\Member;
use App\Models\Address;
use App\Models\Education;
use App\Models\Career;
use App\Models\PhysicalAttribute;
use App\Models\Hobby;
use App\Models\Attitude;
use App\Models\Recidency;
use App\Models\Lifestyle;
use App\Models\Astrology;
use App\Models\Family;
use App\Models\PartnerExpectation;
use App\Models\SpiritualBackground;
use App\Models\PackagePayment;
use App\Models\Call;
use App\Models\HappyStory;
use App\Models\Shortlist;
use App\Models\IgnoredUser;
use App\Models\ReportedUser;
use App\Models\Staff;
use App\Models\GalleryImage;
use App\Models\ExpressInterest;
use App\Models\ProfileMatch;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use App\Notifications\EmailVerificationNotification;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use SoftDeletes;
    use Notifiable;
    use HasRoles;

    public function sendEmailVerificationNotification()
    {
        $this->notify(new EmailVerificationNotification());
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password', 'code', 'phone','membership','approved', 'verification_code','fcm_token','referred_by','email_verified_at', 'match_refresh_updated_at', 'refresh_updated_at', 'admin_identifier'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function member()
    {
        return $this->hasOne(Member::class);
    }

    public function addresses()
    {
        return $this->hasmany(Address::class);
    }

    public function education()
    {
        return $this->hasmany(Education::class);
    }

    public function career()
    {
        return $this->hasmany(Career::class);
    }

    public function physical_attributes()
    {
        return $this->hasOne(PhysicalAttribute::class);
    }

    public function hobbies()
    {
        return $this->hasOne(Hobby::class);
    }

    public function attitude()
    {
        return $this->hasOne(Attitude::class);
    }

    public function recidency()
    {
        return $this->hasOne(Recidency::class);
    }

    public function lifestyles()
    {
        return $this->hasOne(Lifestyle::class);
    }

    public function astrologies()
    {
        return $this->hasOne(Astrology::class);
    }

    public function families()
    {
        return $this->hasOne(Family::class);
    }

    public function partner_expectations()
    {
        return $this->hasOne(PartnerExpectation::class);
    }

    public function spiritual_backgrounds()
    {
        return $this->hasOne(SpiritualBackground::class);
    }

    public function payckage_payments()
    {
        return $this->hasmany(PackagePayment::class);
    }

    public function happy_story()
    {
        return $this->hasOne(HappyStory::class);
    }

    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    public function shortlist()
    {
        return $this->hasmany(Shortlist::class);
    }

    public function ignored_users()
    {
        return $this->hasmany(IgnoredUser::class);
    }

    public function reported_users()
    {
        return $this->hasmany(ReportedUser::class);
    }

    public function callsMade()
    {
        return $this->hasMany(Call::class, 'caller_id');
    }

    public function callsReceived()
    {
        return $this->hasMany(Call::class, 'receiver_id');
    }

    public function gallery_images()
    {
        return $this->hasmany(GalleryImage::class);
    }

    public function express_interests()
    {
        return $this->hasmany(ExpressInterest::class);
    }

    public function sent_proposals()
    {
        return $this->hasMany(ExpressInterest::class, 'interested_by');
    }

    public function received_proposals()
    {
        return $this->hasMany(ExpressInterest::class, 'user_id');
    }
    public function profile_match()
    {
        return $this->hasmany(ProfileMatch::class);
    }
    public function uploads(){
        return $this->hasMany(Upload::class);
    }

    public function profile_views(){
        return $this->hasMany(ProfileView::class);
    }

    public function device_sessions()
    {
        return $this->hasMany(UserDeviceSession::class);
    }

    public function profile_privacy_setting()
    {
        return $this->hasOne(ProfilePrivacySetting::class);
    }

    public function saved_searches()
    {
        return $this->hasMany(SavedSearch::class);
    }

    public function profile_match_for_viewer()
    {
        return $this->hasOne(ProfileMatch::class, 'match_id');
    }

    public function profile_verification_requests()
    {
        return $this->hasMany(ProfileVerificationRequest::class);
    }

    public function notification_preference()
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function push_tokens()
    {
        return $this->hasMany(UserPushToken::class);
    }

    public function guardian_links()
    {
        return $this->hasMany(FamilyGuardianLink::class, 'profile_user_id');
    }

    public function managed_profile_links()
    {
        return $this->hasMany(FamilyGuardianLink::class, 'guardian_user_id');
    }
    public function shouldBlockLoginForManualReview(): bool
    {
        if ($this->user_type !== 'member') {
            return false;
        }

        $member = $this->member;

        return in_array($member?->verification_status, ['manual_review'], true)
            || in_array($member?->ai_verification_status, ['manual_review'], true);
    }
}
