<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ClientScopeSetting extends Model
{
    protected $fillable = ['key', 'value'];
    public static function valueOf(string $key, string $default): string
    {
        return (string) (self::query()->where('key', $key)->value('value') ?? $default);
    }
}
