<?php
namespace App\Http\Controllers\Api;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'vision' => ['nullable','string','max:2000'],
            'mission' => ['nullable','array','max:10'],
            'mission.*' => ['nullable','string','max:300'],
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
    /** Buang butir misi kosong (mis. string kosong yang berubah menjadi null). */
    private function cleanMission(array $data): array
    {
        if (array_key_exists('mission', $data)) {
            $data['mission'] = array_values(array_filter($data['mission'] ?? [], fn ($m) => is_string($m) && trim($m) !== ''));
        }
        return $data;
    }
    public function store(Request $request)
    {
        $data = $this->cleanMission($request->validate($this->rules()));
        $row = $request->user()->courses()->create($data);
        return $this->ok($row, 'Mata kuliah ditambahkan.', 201);
    }
    public function show(Request $request, string $course)
    { return $this->ok($this->own($request, $course)); }
    public function update(Request $request, string $course)
    {
        $row = $this->own($request, $course);
        $row->update($this->cleanMission($request->validate($this->rules())));
        return $this->ok($row->fresh(), 'Mata kuliah diperbarui.');
    }
    public function destroy(Request $request, string $course)
    {
        $this->own($request, $course)->delete();
        return $this->ok(null, 'Mata kuliah dihapus.');
    }

    public function uploadContract(Request $request, string $course)
    {
        $row = $this->own($request, $course);
        $request->validate(['contract' => ['required','file','mimes:pdf,doc,docx','max:5120']]);
        $file = $request->file('contract');
        $size = $file->getSize();
        $name = mb_substr($file->getClientOriginalName(), 0, 255);
        $ext = strtolower($file->getClientOriginalExtension() ?: ($file->guessExtension() ?: 'pdf'));
        $path = $file->storeAs('courses/contracts/' . $request->user()->id, $row->id . '.' . $ext, 'local');
        if ($row->contract_path && $row->contract_path !== $path) Storage::disk('local')->delete($row->contract_path);
        $row->forceFill(['contract_path' => $path, 'contract_name' => $name, 'contract_size' => $size])->save();
        return $this->ok($row->fresh(), 'Kontrak kuliah diunggah.');
    }
    public function downloadContract(Request $request, string $course)
    {
        $row = $this->own($request, $course);
        abort_unless($row->contract_path && Storage::disk('local')->exists($row->contract_path), 404, 'Kontrak kuliah tidak ditemukan.');
        return Storage::disk('local')->download($row->contract_path, $row->contract_name);
    }
    public function deleteContract(Request $request, string $course)
    {
        $row = $this->own($request, $course);
        $row->deleteContractFile();
        $row->forceFill(['contract_path' => null, 'contract_name' => null, 'contract_size' => null])->save();
        return $this->ok($row->fresh(), 'Kontrak kuliah dihapus.');
    }
}
