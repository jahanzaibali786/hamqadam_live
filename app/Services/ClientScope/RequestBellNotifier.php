<?php
declare(strict_types=1);
namespace App\Services\ClientScope;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** Database bell alerts only: no external push/email and no financial actions. */
final class RequestBellNotifier {
 public function created(string $type, int $id, ?int $actorId, string $description, string $adminUrl): void {
  if(!Schema::hasTable('notifications'))return;
  try {
   $admins=User::query()->where('user_type','admin')->where('blocked',0)->pluck('id');
   foreach($admins as $adminId) $this->write((int)$adminId,$type,$id,$actorId,$description,$adminUrl,'created');
  } catch(\Throwable $e){Log::warning('Request alert was not recorded',['type'=>$type,'id'=>$id,'error'=>$e->getMessage()]);}
 }
 public function updated(string $type,int $id,?int $actorId,?int $ownerId,string $description,string $adminUrl,string $memberUrl):void {
  if(!Schema::hasTable('notifications'))return;
  try{
   foreach(User::query()->where('user_type','admin')->where('blocked',0)->pluck('id') as $adminId)
    $this->write((int)$adminId,$type,$id,$actorId,$description,$adminUrl,'updated');
   if($ownerId) $this->write($ownerId,$type,$id,$actorId,$description,$memberUrl,'updated');
  }catch(\Throwable $e){Log::warning('Request update alert failed',['type'=>$type,'id'=>$id,'error'=>$e->getMessage()]);}
 }
 private function write(int $recipient,string $type,int $id,?int $actorId,string $message,string $destination,string $action):void {
  DB::table('notifications')->insert([
   'id'=>(string)Str::uuid(),'type'=>'database','notifiable_type'=>User::class,'notifiable_id'=>$recipient,
   'data'=>json_encode(['type'=>'client_request','event'=>$type.'_'.$action,'title'=>ucwords(str_replace('_',' ',$type)).' #'.$id,'notify_by'=>$actorId,'info_id'=>$id,'message'=>$message,'deep_link'=>$destination,'route'=>$destination],JSON_UNESCAPED_SLASHES),
   'read_at'=>null,'created_at'=>now(),'updated_at'=>now()
  ]);
 }
}
