<?php
namespace App\Support;
use App\Models\Setting;
class HomePageContent {
 public static function defaults(): array {return [
 'hero_kicker'=>'Sanctuary of Dignified Matrimony','hero_title_first'=>'Find Your','hero_title_emphasis'=>'Perfect Partner','hero_title_last'=>'With Trust & Care.',
 'hero_description'=>'Join thousands of happy couples on HamQadam, where meaningful relationships begin. Verified profiles, secure connections, and genuine matches tailored for you.',
 'hero_guest_button'=>'Get Started','hero_member_button'=>'Find Matches','hero_secondary_button'=>'Learn More',
 'hero_stat_one'=>'Verified Members','hero_stat_two'=>'Success Stories','hero_stat_three'=>'Secure & Private',
 'hero_match_label'=>'Match','hero_verified_label'=>'ID Verified','hero_sanctuary_label'=>'Private Sanctuary','hero_sanctuary_caption'=>'Where pure intentions meet companionship.',
 'hero_premium_label'=>'Premium Match','hero_standard_label'=>'Verified Match',
 'search_title'=>'Quick Partner Search','search_subtitle'=>'Filter through verified prospective life partners','search_verified'=>'Real-time Verification Active',
 'search_gender'=>'Looking For','search_any'=>'Any','search_bride'=>'Bride (Female)','search_groom'=>'Groom (Male)','search_min_age'=>'Age From','search_max_age'=>'Age To','search_religion'=>'Religion & Sect','search_any_religion'=>'Any Religion','search_language'=>'Mother Language','search_any_language'=>'Any Language','search_button'=>'Search',
 'premium_title'=>'Premium Members','premium_description'=>'Explore verified premium profiles','new_title'=>'New Members','new_description'=>'Meet our newest members',
 'hero_female_fallback'=>'Laiba','hero_male_fallback'=>'Ubaid','hero_fallback_city_female'=>'Lahore','hero_fallback_city_male'=>'Islamabad',
 'registration_button'=>'Create Account','stories_more'=>'View More','blogs_more'=>'View More','packages_purchase'=>'Purchase This Package','registration_gift'=>'Auspicious Welcome Gift','registration_title'=>'Create Your Account','registration_reward_prefix'=>'Register now and get reward of','registration_reward_mid'=>'coins from the','registration_reward_end'=>'instantly upon verification.','registration_step_account'=>'Account','registration_step_basic'=>'Basic','registration_step_religion'=>'Religion','registration_step_location'=>'Location','registration_terms_prefix'=>'By signing up you agree to our','registration_terms_link'=>'terms and conditions','story_more'=>'View More','package_coins'=>'Coins','package_gallery'=>'Gallery Photo Upload','package_contacts'=>'Contact Info View','package_auto_match'=>'Show Auto Profile Match','package_horoscope'=>'Show Auto Horoscope Profile Match','package_free'=>'Free','package_days'=>'Days'
 ];}
 public static function all(): array {
  $stored=json_decode((string)get_setting('hq_home_page_content',''),true);
  $defaults=self::defaults();
  // Start from existing homepage settings on the first CMS edit, so the live
  // content remains unchanged until an admin saves this editor.
  if(!is_array($stored)){
   foreach(['premium_title'=>'premium_member_section_title','premium_description'=>'premium_member_section_sub_title','new_title'=>'new_member_section_title','new_description'=>'new_member_section_sub_title'] as $key=>$setting){
    if(filled(get_setting($setting))) $defaults[$key]=(string)get_setting($setting);
   }
   return $defaults;
  }
  return array_replace($defaults,array_intersect_key($stored,$defaults));
 }
 public static function get(string $key): string {return (string)(self::all()[$key]??'');}
}
