<?php
declare(strict_types=1);
namespace App\Services\ClientScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
final class GeographicHeatmapService {
    public function report(): array {
        if(!Schema::hasTable('addresses')||!Schema::hasTable('city_geo_coordinates')||!Schema::hasTable('users')) return ['pins'=>[],'mapped_members'=>0,'unmapped_members'=>0];
        $rows=DB::table('addresses as a')->join('users as u','u.id','=','a.user_id')
            ->leftJoin('city_geo_coordinates as geo','geo.city_id','=','a.city_id')
            ->leftJoin('cities as c','c.id','=','a.city_id')
            ->where('u.user_type','member')->whereNull('u.deleted_at')
            ->whereNull('a.deleted_at')->whereNotNull('a.city_id')
            ->selectRaw('a.city_id,MAX(c.name) as city,MAX(geo.latitude) as latitude,MAX(geo.longitude) as longitude,COUNT(DISTINCT u.id) as members')
            ->groupBy('a.city_id')->orderByDesc('members')->limit(250)->get();
        return ['pins'=>$rows->filter(fn($r)=>$r->latitude!==null&&$r->longitude!==null)->values()->map(fn($r)=>[
            'city_id'=>(int)$r->city_id,'city'=>$r->city,'latitude'=>(float)$r->latitude,
            'longitude'=>(float)$r->longitude,'members'=>(int)$r->members
        ])->all(),'mapped_members'=>(int)$rows->filter(fn($r)=>$r->latitude!==null)->sum('members'),
        'unmapped_members'=>(int)$rows->filter(fn($r)=>$r->latitude===null)->sum('members')];
    }
}
