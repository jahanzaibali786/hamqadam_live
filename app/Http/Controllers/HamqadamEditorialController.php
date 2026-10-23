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
        return view('frontend.editorial.base', ['data'=>$data,'pageKey'=>$page]);
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


    public function consultation(Request $request)
    {
        $data = self::content('team');
        $cities = array_column($data['consult']['cities'], 'value');
        $advisors = array_column($data['consult']['advisors'], 'value');
        $valid = $request->validate([
            'name'=>['required','string','max:120'],
            'email'=>['required','email','max:200'],
            'phone'=>['required','string','max:35'],
            'city'=>['required','string',\Illuminate\Validation\Rule::in($cities)],
            'advisor'=>['required','string',\Illuminate\Validation\Rule::in($advisors)],
            'website'=>['nullable','max:0'],
        ]);
        $to = (string) get_setting('footer_email');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors(['consultation'=>'The consultation desk is not configured yet. Please use the helpline.'])->withInput();
        }
        try {
            \Illuminate\Support\Facades\Mail::raw(
                "Advisor consultation request\n\nName: ".$valid['name']."\nEmail: ".$valid['email']."\nPhone: ".$valid['phone']."\nCity: ".$valid['city']."\nAdvisor: ".$valid['advisor'],
                function ($mail) use ($to,$valid) {
                    $mail->to($to)->replyTo($valid['email'])->subject('Hamqadam: Advisor Consultation Request');
                }
            );
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['consultation'=>'Could not submit right now. Please contact the family support desk directly.'])->withInput();
        }
        return back()->with('advisor_request_status','Thank you. Your consultation request was sent to our support desk.');
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
