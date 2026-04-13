<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model {
    protected $fillable = ['user_id','ip_address','user_agent','location','success','logged_at'];
    protected $casts    = ['logged_at' => 'datetime', 'success' => 'boolean'];
    public function user() { return $this->belongsTo(User::class); }
}
