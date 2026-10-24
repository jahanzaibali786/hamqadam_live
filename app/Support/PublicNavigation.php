<?php
namespace App\Support;
class PublicNavigation {
 public static function items(string $area): array {
  $defaults = $area==='header' ? [
   ['label'=>'Home','url'=>'/'],['label'=>'About Us','url'=>'/about-us'],['label'=>'Our Team','url'=>'/our-team'],
   ['label'=>'Success Stories','url'=>'/happy-stories'],['label'=>'Blogs','url'=>'/blog'],['label'=>'Help & Support','url'=>'/contact-us/page']
  ] : [
   ['label'=>'Home','url'=>'/'],['label'=>'Active Members','url'=>'/active-members'],['label'=>'Success Stories','url'=>'/happy-stories'],['label'=>'Premium Plans','url'=>'/packages']
  ];
  $raw=get_setting('hq_public_nav_'.$area,'');
  if($raw==='')return $defaults;
  $decoded=json_decode((string)$raw,true);
  return is_array($decoded) ? array_values(array_filter($decoded,fn($i)=>is_array($i)&&!empty($i['label'])&&!empty($i['url']))) : $defaults;
 }
 public static function link(string $url): string {
  if(str_starts_with($url,'/') && !str_starts_with($url,'//'))return url($url);
  if(filter_var($url,FILTER_VALIDATE_URL) && parse_url($url,PHP_URL_SCHEME)==='https')return $url;
  return url('/');
 }
}
