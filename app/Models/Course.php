<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Course extends Model
{
    use HasUuids;
    protected $table = 'getdone_courses';
    protected $fillable = ['code','title','credits','description','lecturer','schedule_day','schedule_time','room','image_url','accent','progress','semester_status','semester_number','result_status'];
    protected function casts(): array { return ['credits' => 'integer', 'progress' => 'integer', 'semester_number' => 'integer']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function tasks(): HasMany { return $this->hasMany(Task::class); }
}
