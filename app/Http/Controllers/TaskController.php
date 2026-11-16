<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Task::class);

        $filters = $request->only(['q', 'status', 'priority', 'assigned_to', 'overdue', 'due']);

        $tasks = Task::query()
            ->with(['assignee', 'creator', 'taskable'])
            ->visibleTo($request->user())
            ->filter($filters)
            ->latest('due_at')
            ->paginate(20)
            ->withQueryString();

        $overdueCount = Task::query()->visibleTo($request->user())->overdue()->count();

        return view('tasks.index', [
            'tasks' => $tasks,
            'filters' => $filters,
            'overdueCount' => $overdueCount,
            'priorities' => config('phoenix.lead_priorities'),
            'statuses' => config('phoenix.task_statuses'),
            'agents' => $this->assignableUsers(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Task::class);

        $lead = $request->filled('lead_id') ? Lead::find($request->integer('lead_id')) : null;

        return view('tasks.create', [
            'lead' => $lead,
            'priorities' => config('phoenix.lead_priorities'),
            'agents' => $this->assignableUsers(),
            'leads' => Lead::query()->visibleTo($request->user())->with('customer')->latest()->limit(100)->get(),
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $this->authorize('create', Task::class);

        $data = $request->validated();
        $taskable = null;

        if (! empty($data['lead_id'])) {
            $taskable = Lead::findOrFail($data['lead_id']);
            $this->authorize('view', $taskable);
        } elseif (! empty($data['customer_id'])) {
            $taskable = Customer::findOrFail($data['customer_id']);
        }

        $task = $this->taskService->create($taskable, $data, $request->user());

        if ($taskable instanceof Lead) {
            return redirect()
                ->route('leads.show', $taskable)
                ->with('success', 'Task created.');
        }

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task "'.$task->title.'" created.');
    }

    public function edit(Task $task): View
    {
        $this->authorize('update', $task);

        return view('tasks.edit', [
            'task' => $task->load(['taskable', 'assignee']),
            'priorities' => config('phoenix.lead_priorities'),
            'agents' => $this->assignableUsers(),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $this->taskService->update($task, $request->validated(), $request->user());

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task updated.');
    }

    public function complete(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('complete', $task);

        $this->taskService->complete($task, $request->user());

        return back()->with('success', 'Task marked complete.');
    }

    private function assignableUsers()
    {
        return User::query()->where('is_active', true)->orderBy('name')->get();
    }
}
