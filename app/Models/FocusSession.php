<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class FocusSession extends Model
{
    use HasUuids;
    protected $table = 'getdone_focus_sessions';
    protected $fillable = ['task_id','task_title','duration_minutes','completed_at'];
    protected function casts(): array { return ['completed_at' => 'datetime', 'duration_minutes' => 'integer']; }
}
