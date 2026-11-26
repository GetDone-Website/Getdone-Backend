<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
class Course extends Model
{
    use HasUuids;
    protected $table = 'getdone_courses';
    // Kolom kontrak sengaja tidak masuk $fillable: hanya diubah lewat endpoint kontrak.
    protected $fillable = ['code','title','credits','description','vision','mission','lecturer','schedule_day','schedule_time','room','image_url','accent','progress','semester_status','semester_number','result_status'];
    protected $hidden = ['contract_path','contract_name','contract_size'];
    protected $appends = ['contract'];
    protected function casts(): array { return ['credits' => 'integer', 'progress' => 'integer', 'semester_number' => 'integer']; }

    protected static function booted(): void
    {
        static::deleting(function (Course $course) { $course->deleteContractFile(); });
    }

    /** Misi disimpan sebagai JSON; selalu dikembalikan sebagai array (kosong bila belum diisi). */
    protected function mission(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (is_array($value)) return $value;
                $decoded = is_string($value) ? json_decode($value, true) : null;
                return is_array($decoded) ? array_values($decoded) : [];
            },
            set: fn ($value) => json_encode(array_values(is_array($value) ? $value : []), JSON_UNESCAPED_UNICODE),
        );
    }

    /** Info kontrak untuk klien: {name, size} atau null. Jalur file tidak pernah dibuka. */
    protected function contract(): Attribute
    {
        return Attribute::make(get: fn () => $this->contract_path
            ? ['name' => $this->contract_name, 'size' => (int) $this->contract_size]
            : null);
    }

    public function deleteContractFile(): void
    {
        if ($this->contract_path) Storage::disk('local')->delete($this->contract_path);
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function tasks(): HasMany { return $this->hasMany(Task::class); }
}
