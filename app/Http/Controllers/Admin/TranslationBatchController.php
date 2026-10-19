<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TranslationBatchController extends Controller
{
    public function export(string $language): StreamedResponse
    {
        abort_unless(in_array($language, ['en','ur','ar','hi'], true),404);
        abort_unless(Language::where('code',$language)->exists(),404);
        return response()->streamDownload(function() use ($language): void {
            $out=fopen('php://output','wb');
            fputcsv($out,['lang_key','lang_value']);
            Translation::query()->where('lang','en')->orderBy('lang_key')->chunk(250,function($keys)use($language,$out): void {
                $translations=Translation::query()->where('lang',$language)
                    ->whereIn('lang_key',$keys->pluck('lang_key'))->pluck('lang_value','lang_key');
                foreach($keys as $key) {
                    $val=(string)($translations->get($key->lang_key)??'');
                    // Prevent CSV spreadsheet formula evaluation if opened in Excel.
                    $safe=fn(string $v): string => preg_match('/^[=+@\-]/u',$v)?("'".$v):$v;
                    fputcsv($out,[$safe((string)$key->lang_key),$safe($val)]);
                }
            });
            fclose($out);
        },'hamqadam-translations-'.$language.'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }

    public function import(Request $request)
    {
        $data=$request->validate([
            'language'=>['required','in:ur,ar,hi,en','exists:languages,code'],
            'file'=>['required','file','mimes:csv,txt','max:2048'],
        ]);
        $fh=fopen($request->file('file')->getRealPath(),'rb');
        if($fh===false) throw ValidationException::withMessages(['file'=>'Cannot read CSV.']);
        try {
            $head=fgetcsv($fh);
            if(!is_array($head)||ltrim((string)($head[0]??''),"\xEF\xBB\xBF")!=='lang_key'||($head[1]??'')!=='lang_value'){
                throw ValidationException::withMessages(['file'=>'Expected CSV columns: lang_key,lang_value.']);
            }
            $rows=[];$count=0;
            while(($line=fgetcsv($fh))!==false){
                if(++$count>5000) throw ValidationException::withMessages(['file'=>'Maximum 5000 translations per import.']);
                $key=trim((string)($line[0]??''));$value=trim((string)($line[1]??''));
                if($key===''||$value==='')continue;
                if(mb_strlen($key)>255||mb_strlen($value)>10000||strip_tags($value)!==$value){
                    throw ValidationException::withMessages(['file'=>'Translation row contains invalid length or HTML tags.']);
                }
                $rows[$key]=$value;
            }
        } finally { fclose($fh); }

        $reference=Translation::query()->where('lang','en')->whereIn('lang_key',array_keys($rows))->pluck('lang_key')->all();
        $unknown=array_diff(array_keys($rows),$reference);
        if($unknown)throw ValidationException::withMessages(['file'=>'Unknown reference key(s): '.implode(', ',array_slice($unknown,0,5))]);
        DB::transaction(function() use($rows,$data): void {
            foreach($rows as $key=>$value) {
                $line=Translation::query()->where('lang',$data['language'])->where('lang_key',$key)->first()??new Translation();
                $line->lang=$data['language'];$line->lang_key=$key;$line->lang_value=$value;$line->save();
            }
        },3);
        Cache::forget('translations-'.$data['language']);
        return back()->with('success',count($rows).' approved translation keys imported.');
    }
}
