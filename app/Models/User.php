<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasUuids, Notifiable;
    protected $table = 'getdone_users';
    protected $fillable = ['full_name', 'nim', 'email', 'university', 'avatar_url', 'password', 'onboarding_data'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array
    {
        return ['password' => 'hashed', 'onboarding_data' => 'array', 'email_verified_at' => 'datetime'];
    }
    public function courses(): HasMany { return $this->hasMany(Course::class); }
    public function tasks(): HasMany { return $this->hasMany(Task::class); }
    public function focusSessions(): HasMany { return $this->hasMany(FocusSession::class); }
}
