<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\CityGeoCoordinate;
use Illuminate\Http\Request;
final class CityGeoCoordinateController extends Controller {
    public function store(Request $request) {
        $data=$request->validate([
            'city_id'=>['required','integer','exists:cities,id'],
            'latitude'=>['required','numeric','between:-90,90'],
            'longitude'=>['required','numeric','between:-180,180']
        ]);
        CityGeoCoordinate::updateOrCreate(['city_id'=>$data['city_id']],[
            'latitude'=>$data['latitude'],'longitude'=>$data['longitude'],'updated_by'=>$request->user()->id
        ]);
        return back()->with('success','City geographic coordinates saved.');
    }
}
