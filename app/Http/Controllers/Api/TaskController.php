<?php
namespace App\Http\Controllers\Api;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends ApiController
{
    private function rules(Request $request): array
    {
        return [
            'course_id' => ['nullable','uuid',Rule::exists('getdone_courses','id')->where('user_id', $request->user()->id)],
            'title' => ['required','string','max:255'],
            'description' => ['nullable','string','max:15000'],
            'due_date' => ['required','date'],
            'priority' => ['required',Rule::in(['low','medium','high'])],
            'status' => ['required',Rule::in(['todo','in_progress','completed'])],
            'completed_at' => ['nullable','date'],
        ];
    }
    private function own(Request $request, string $id): Task
    { return $request->user()->tasks()->findOrFail($id); }
    private function normalise(array $data): array
    {
        if ($data['status'] === 'completed' && empty($data['completed_at'])) $data['completed_at'] = now();
        if ($data['status'] !== 'completed') $data['completed_at'] = null;
        return $data;
    }
    public function index(Request $request)
    { return $this->ok($request->user()->tasks()->orderBy('due_date')->get()); }
    public function store(Request $request)
    {
        $row = $request->user()->tasks()->create($this->normalise($request->validate($this->rules($request))));
        return $this->ok($row, 'Tugas ditambahkan.', 201);
    }
    public function show(Request $request, string $task)
    { return $this->ok($this->own($request, $task)); }
    public function update(Request $request, string $task)
    {
        $row = $this->own($request, $task);
        $row->update($this->normalise($request->validate($this->rules($request))));
        return $this->ok($row->fresh(), 'Tugas diperbarui.');
    }
    public function destroy(Request $request, string $task)
    {
        $this->own($request, $task)->delete();
        return $this->ok(null, 'Tugas dihapus.');
    }
}
