<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SponsoredListing extends Model {
    protected $guarded = [];
    protected $casts = ['active'=>'boolean','starts_at'=>'datetime','ends_at'=>'datetime','eligible_plan_ids'=>'array'];
    public function scopeLive($q){ return $q->where('active',1)->where(fn($x)=>$x->whereNull('starts_at')->orWhere('starts_at','<=',now()))->where(fn($x)=>$x->whereNull('ends_at')->orWhere('ends_at','>=',now())); }
    public function user(){ return $this->belongsTo(User::class); }
}
