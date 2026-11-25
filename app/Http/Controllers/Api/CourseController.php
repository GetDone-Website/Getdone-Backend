<?php
namespace App\Http\Controllers\Api;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseController extends ApiController
{
    private function rules(): array
    {
        return [
            'code' => ['required','string','max:32'],
            'title' => ['required','string','max:200'],
            'credits' => ['required','integer','between:1,6'],
            'description' => ['nullable','string','max:8000'],
            'lecturer' => ['nullable','string','max:200'],
            'schedule_day' => ['nullable','string','max:30'],
            'schedule_time' => ['nullable','string','max:100'],
            'room' => ['nullable','string','max:150'],
            'image_url' => ['nullable','string','max:2000'],
            'accent' => ['nullable','string','max:100'],
            'progress' => ['nullable','integer','between:0,100'],
            'semester_status' => ['nullable', Rule::in(['current','passed','failed'])],
            'semester_number' => ['nullable','integer','between:1,100'],
            'result_status' => ['nullable',Rule::in(['','passed','failed'])],
        ];
    }
    private function own(Request $request, string $id): Course
    { return $request->user()->courses()->findOrFail($id); }
    public function index(Request $request)
    { return $this->ok($request->user()->courses()->orderBy('created_at')->get()); }
    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $row = $request->user()->courses()->create($data);
        return $this->ok($row, 'Mata kuliah ditambahkan.', 201);
    }
    public function show(Request $request, string $course)
    { return $this->ok($this->own($request, $course)); }
    public function update(Request $request, string $course)
    {
        $row = $this->own($request, $course);
        $row->update($request->validate($this->rules()));
        return $this->ok($row->fresh(), 'Mata kuliah diperbarui.');
    }
    public function destroy(Request $request, string $course)
    {
        $this->own($request, $course)->delete();
        return $this->ok(null, 'Mata kuliah dihapus.');
    }
}
