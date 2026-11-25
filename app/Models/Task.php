<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Task extends Model
{
    use HasUuids;
    protected $table = 'getdone_tasks';
    protected $fillable = ['course_id','title','description','due_date','priority','status','completed_at'];
    protected function casts(): array { return ['due_date' => 'datetime', 'completed_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
}
