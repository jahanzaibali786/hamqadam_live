<?php
namespace App\Http\Controllers;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\HappyStory;
use App\Support\PublicEditorialPages;
use Illuminate\Http\Request;
class PublicEditorialListingController extends Controller {
 public function blogs(Request $request){
  $data=PublicEditorialPages::copy('blogs');
  $search=trim((string)$request->query('search',''));$category=trim((string)$request->query('category',''));
  if(mb_strlen($search)>120 || mb_strlen($category)>150) abort(422);
  $categories=BlogCategory::query()->whereHas('posts',fn($q)=>$q->where('status',1))->orderBy('category_name')->get();
  $q=Blog::query()->with('category')->where('status',1);
  if($search!=='') $q->where(fn($q)=>$q->where('title','like','%'.addcslashes($search,'%_\\').'%')->orWhere('short_description','like','%'.addcslashes($search,'%_\\').'%'));
  if($category!=='') $q->whereHas('category',fn($q)=>$q->where('slug',$category));
  $featured=Blog::query()->with('category')->where('status',1)->whereNotNull('banner')->latest()->first();
  $blogs=$q->latest()->paginate(9)->withQueryString();
  return view('frontend.blog.listing',compact('data','categories','featured','blogs','search','category'));
 }
 public function stories(){
  $data=PublicEditorialPages::copy('stories');
  $featured=HappyStory::query()->with('user')->where('approved',1)->whereNotNull('photos')->latest()->first();
  $happy_stories=HappyStory::query()->with('user')->where('approved',1)->latest()->paginate(12);
  return view('frontend.happy_stories.index',compact('data','featured','happy_stories'));
 }
}