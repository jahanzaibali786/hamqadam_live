<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HamqadamEditorialController extends Controller
{
    public const PAGES = ['about'=>'about-us','team'=>'our-team','privacy'=>'privacy-policy','terms'=>'terms-conditions'];

    public static function defaults(string $page): array
    {
        abort_unless(array_key_exists($page, self::PAGES), 404);
        $all = json_decode(file_get_contents(resource_path('data/hamqadam_editorial_pages.json')), true, 512, JSON_THROW_ON_ERROR);
        return $all[$page] ?? [];
    }

    public static function content(string $page): array
    {
        $default = self::defaults($page);
        $saved = json_decode((string) get_setting('hq_editorial_'.$page, ''), true);
        return is_array($saved) && isset($saved['hero']) ? $saved : $default;
    }

    public function show(string $page)
    {
        $data = self::content($page);
        return view('frontend.editorial.'.$page, ['data'=>$data,'pageKey'=>$page]);
    }

    public function edit(string $page)
    {
        $data = self::content($page);
        return view('admin.website_settings.editorial_page',compact('data','page'));
    }

    /**
     * Save typed JSON fields only. A complete document is validated recursively
     * against the checked-in schema; arbitrary markup cannot be injected.
     */
    public function update(Request $request, string $page)
    {
        $request->validate(['payload'=>['required','string','max:250000']]);
        try {
            $provided = json_decode($request->input('payload'), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return back()->withErrors(['payload'=>'Invalid page data.'])->withInput();
        }
        $schema = self::defaults($page);
        try {
            $clean = $this->sanitize($provided,$schema,0);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['payload'=>$e->getMessage()])->withInput();
        }
        Setting::updateOrCreate(['type'=>'hq_editorial_'.$page],['value'=>json_encode($clean,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
        Cache::forget('settings');
        return redirect()->back()->with('success','Page content saved successfully.');
    }

    private function sanitize(mixed $value, mixed $schema, int $depth): mixed
    {
        if ($depth>14) throw new \InvalidArgumentException('Content nesting is too deep.');
        if (is_string($schema)) {
            if (!is_string($value) || mb_strlen($value)>4000) throw new \InvalidArgumentException('A text field is invalid or too long.');
            $value = trim($value);
            if (preg_match('/^\s*(javascript|data|vbscript):/i',$value)) throw new \InvalidArgumentException('Unsafe URL.');
            return $value;
        }
        if (is_int($schema)) {
            if (!is_numeric($value) || (int)$value<0 || (int)$value>999999999) throw new \InvalidArgumentException('Invalid image ID.');
            $id=(int)$value;
            if ($id>0 && !\App\Models\Upload::whereKey($id)->exists()) throw new \InvalidArgumentException('Image must exist in the Media Library.');
            return $id;
        }
        if (!is_array($schema) || !is_array($value)) throw new \InvalidArgumentException('Invalid section type.');
        if (array_is_list($schema)) {
            if (!array_is_list($value) || count($value)>40) throw new \InvalidArgumentException('Invalid or oversized repeatable section.');
            $template = $schema[0] ?? null;
            if ($template === null) return [];
            return array_map(fn($item)=>$this->sanitize($item,$template,$depth+1),$value);
        }
        $clean=[];
        foreach ($schema as $key=>$child) $clean[$key]=$this->sanitize($value[$key]??$child,$child,$depth+1);
        return $clean;
    }
}
