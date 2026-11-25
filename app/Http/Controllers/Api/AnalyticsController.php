<?php
namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AnalyticsController extends ApiController
{
    private function metrics(Request $request): array
    {
        $tasks = $request->user()->tasks();
        $total = (clone $tasks)->count();
        $completed = (clone $tasks)->where('status','completed')->count();
        $active = $total - $completed;
        $urgent = (clone $tasks)->where('status', '!=', 'completed')->where('due_date', '<=', now()->addDay())->count();
        $focusMinutes = $request->user()->focusSessions()->sum('duration_minutes');
        return compact('total','completed','active','urgent','focusMinutes') + ['completion_rate' => $total ? round($completed / $total * 100, 1) : 0];
    }
    public function dashboard(Request $request)
    {
        return $this->ok(['metrics' => $this->metrics($request), 'upcoming_tasks' => $request->user()->tasks()->where('status','!=','completed')->orderBy('due_date')->limit(8)->get()]);
    }
    public function index(Request $request)
    {
        $counts = $request->user()->tasks()->select('course_id', DB::raw('COUNT(*) as task_count'))->groupBy('course_id')->get();
        return $this->ok(['metrics' => $this->metrics($request), 'workload' => $counts,
            'focus_sessions' => $request->user()->focusSessions()->orderByDesc('completed_at')->limit(100)->get()]);
    }
    public function storeFocus(Request $request)
    {
        $data = $request->validate([
            'task_id' => ['nullable','uuid',Rule::exists('getdone_tasks','id')->where('user_id',$request->user()->id)],
            'task_title' => ['required','string','max:255'],
            'duration_minutes' => ['required','integer','between:1,120'],
            'completed_at' => ['required','date'],
        ]);
        $row = $request->user()->focusSessions()->create($data);
        return $this->ok($row, 'Sesi fokus dicatat.', 201);
    }
}
