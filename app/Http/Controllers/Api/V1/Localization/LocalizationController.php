<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Localization;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Language;
use App\Models\Translation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Bounded mobile translation API. Never asserts that missing translations exist. */
class LocalizationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lang'=>['nullable','in:en,ur,ar,hi'],
            'page'=>['nullable','integer','min:1','max:1000'],
            'per_page'=>['nullable','integer','min:1','max:250'],
        ]);
        $language = $data['lang'] ?? 'en';
        $row = Language::query()->where('code', $language)->first();
        if (! $row) {
            return $this->success(['language'=>$language,'available'=>false,
                'direction'=>in_array($language,['ar','ur'],true)?'rtl':'ltr',
                'translations'=>[], 'message'=>'This language has not been configured by the administrator.']);
        }
        $perPage = (int)($data['per_page'] ?? 100);
        $translations = Translation::query()->where('lang',$language)
            ->orderBy('lang_key')->paginate($perPage, ['lang_key','lang_value'],'page',(int)($data['page'] ?? 1));
        $translatedKeys = Translation::query()->where('lang',$language)->whereNotNull('lang_value')->where('lang_value','!=','')->count();
        $referenceKeys = Translation::query()->where('lang', 'en')->count();
        $coveragePct = $referenceKeys > 0 ? round(100 * min($translatedKeys, $referenceKeys) / $referenceKeys, 1) : null;
        return $this->success([
            'language'=>$language,'available'=>true,
            'translation_coverage' => ['non_empty_keys' => $translatedKeys, 'reference_keys' => $referenceKeys, 'coverage_pct' => $coveragePct, 'is_complete' => false],
            'direction'=>(bool)$row->rtl?'rtl':'ltr',
            'translations'=>$translations->getCollection()->mapWithKeys(
                fn ($line) => [$line->lang_key => $line->lang_value]
            )->all(),
            'page'=>$translations->currentPage(),
            'last_page'=>$translations->lastPage(),
            'total_keys'=>$translations->total(),
            'offline_cache_hint'=>'Store translated strings locally with an expiry; do not cache private profiles.',
        ]);
    }
}
