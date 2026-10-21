<?php
declare(strict_types=1);
namespace App\Services\ClientScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
final class TranslationCoverageService {
    public function report(): array {
        if(!Schema::hasTable('translations'))return [];
        $reference=DB::table('translations')->where('lang','en')->whereNotNull('lang_key')->pluck('lang_key')->unique()->values();
        $total=$reference->count();
        $rows=[];
        foreach(['en','ur','ar','hi'] as $lang){
            $translated=DB::table('translations')->where('lang',$lang)->whereIn('lang_key',$reference->all())
                ->whereNotNull('lang_value')->where('lang_value','!=','')->pluck('lang_key')->unique();
            $missing=$reference->diff($translated)->take(30)->values()->all();
            $rows[]=['language'=>$lang,'reference_keys'=>$total,'translated'=>$translated->count(),
                'coverage_pct'=>$total?round(100*$translated->count()/$total,1):null,
                'sample_missing_keys'=>$missing];
        }
        return $rows;
    }
}
