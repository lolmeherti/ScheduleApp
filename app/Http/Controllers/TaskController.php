<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Carbon\{
    Carbon,
    CarbonPeriod
};
use Illuminate\{
    Http\JsonResponse,
    Http\RedirectResponse,
    Http\Request,
    Support\Facades\Auth,
    Support\Facades\DB,
    Support\Facades\Log,
    View\View
};
class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request $request
     * @return View
     */
    public function index(Request $request) : View
    {
        $carbonNow         = Carbon::now();
        $dateForWeekSelect = $carbonNow->format('d/m/Y');

        if ($request->has('selected_week')) {// this comes from the date-picker on index
            $selectedWeek = TaskCompletionController::getCarbonDateFromDateString($request->input('selected_week'));

            $startOfWeek = $selectedWeek->startOfWeek()->format('d-m-Y H:i');
            $endOfWeek   = $selectedWeek->EndOfWeek()->format('d-m-Y H:i');

            $dateForWeekSelect = $request->input('selected_week');
        }

        //by default, this function returns the current week
        //the function returns an array with all carbon day objects
        //between the two dates
        $days = $this->getAllDaysBetweenTwoDates($startOfWeek ?? "", $endOfWeek ?? "");

        $tasksWithCompletions = $this->mergeTasksWithTheirCompletions($days);

        return view('task.list', compact('days', 'tasksWithCompletions', 'dateForWeekSelect'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request               $request
     * @return RedirectResponse|null
     */
    public function store(Request $request): RedirectResponse|null
    {
        //tasks table section
        $validatedRequest = $request->validate(
            [
                'title'             => ['required', 'string', 'max:500', 'unique:tasks'],
                'description'       => ['string', 'max:1000', 'unique:tasks'],
                'datepicker_create' => ['required_without:repeating'],
                'repeating'         =>
                    [
                        'required_without_all:monday,tuesday,wednesday,thursday,friday,saturday,sunday,datepicker_create, nullable'
                    ],
                "timepicker"        => "required|string"
            ]);

        if (empty($validatedRequest)) {
            return redirect()->back()->with('error', 'Error validating task request');
        }

        $taskToInsert = [
            'title'       => $validatedRequest['title'],
            'description' => $validatedRequest['description'],
            'repeating'   => $validatedRequest['repeating'] ?? "off",
            'user_fid'    => Auth::id(),
            'time_due'    => $validatedRequest['timepicker'] ?? null,
            'date_due'    => $validatedRequest['datepicker_create'] ?? null
        ];

        $daysChecked = false;

        if(isset($validatedRequest["repeating"])) {
            $days = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"];

            foreach ($days as $day) {
                !$request->input($day) || (($daysChecked = true));

                $taskToInsert[$day] = $request->input($day) ?? "off";
            }

            !$daysChecked ?: $taskToInsert["date_due"] = null;
        }

        if (!$daysChecked) {
            $dayToInsert = $this->returnCorrectWeekdayIfNoWeekDaysSelected($validatedRequest["datepicker_create"]);

            $taskToInsert[$dayToInsert] = "on";
        }

        $insertTaskId = DB::table('tasks')->insertGetId($taskToInsert);

        if(!isset($insertTaskId)) {
            return redirect()->back()->with('Error', 'Failed to retrieve the ID of the newly inserted task!');
        }

        //task completion table section
        // Store task completion
        (new TaskCompletionController)->store($request, $insertTaskId);
        return redirect()->back()->with('Success', 'Task created successfully!');
    }

    /**
     * Display the specified resource.
     * This function ends up being called by Ajax from the edit view
     *
     * @param  Request      $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $editFormData = $this->getTaskById($request->id);
        if ($editFormData) {
            return response()->json([
                'status' => 200,
                'task'   => $editFormData
            ]);
        } else {
            return response()->json([
                'status'  => 404,
                'message' => 'Task not found!',
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function edit(Request $request) : RedirectResponse
    {
        $request->validate(
            [
                'title'           => ['required', 'string', 'max:500', 'unique:tasks'],
                'description'     => ['string', 'max:1000', 'unique:tasks'],
                'datepicker_edit' => 'required_without:repeating',
                'repeating'       => 'required_without:datepicker_edit',
            ]);

        $user_fid = $request->user_fid > 0 ? $request->user_fid : Auth::id();

        $days = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"];

        foreach ($days as $day) {
            $updateDates[$day] = $request->input($day);
        }

        Task::where('id', $request->id)
            ->update(array_merge(
                [
                'title'       => $request->input('title'),
                'description' => $request->input('description'),
                'repeating'   => $request->input('repeating'),
                'user_fid'    => $user_fid,
                'time_due'    => $request->input('timepicker_edit'),
                'date_due'    => $request->input('datepicker_edit'),
                'updated_at'  => now()
                ],
                $updateDates
                )
            );

        //task completion table section
        (new TaskCompletionController)->store($request, $request->id);

        //task completion table section

        return redirect()->back()->with('success', 'task created successfully!');
    }

    /**
     * Remove the specified resource from storage.
     * This function only gets called from TaskCompletionController by design
     * We are already making sure that a taskId is passed and that it is a valid Integer
     *
     * @param  int  $taskId
     * @return void
     */
    public function destroy(int $taskId): void
    {
        Task::where('id', $taskId)->delete();
    }

    /**
     * Returns all days between two specific dates.
     * If parameters are not set, it returns all days in the current week
     *
     * @param  string  $startDate
     * @param  string  $endDate
     * @return array{}
     */
    private static function getAllDaysBetweenTwoDates(string $startDate = "", string $endDate = ""): array
    {
        $now = Carbon::now();

        if (!$startDate) {
            $startDate = $now->startOfWeek()->format('d-m-Y H:i');
        }

        if (!$endDate) {
            $endDate = $now->endOfWeek()->format('d-m-Y H:i');
        }

        return CarbonPeriod::create($startDate, $endDate)->toArray();
    }

    /**
     * Returns all tasks on the given days.
     * Given days are an array of Carbon dates.
     * If parameter is not set, it returns all tasks.
     *
     * @param  string $dayOfWeek
     * @param  string $dateOfDay
     * @return array
     */
    public function getTasksForDayOfWeek(string $dayOfWeek = "", string $dateOfDay = "") : array
    {
        if ($dayOfWeek) {
            $tasks = DB::table('tasks')

                // tasks which are repeating are not date sensitive
                // we are fetching all tasks which are repeating on the
                // specific day of the week
                ->where(strtolower($dayOfWeek), ['on'])
                ->where('user_fid', [Auth::id()])
                ->where('repeating', ['on'])

                // tasks which are not repeating are date sensitive
                // these need to match the day AND the date
                ->orWhere(strtolower($dayOfWeek), ['on'])
                ->where('date_due', '<=', $dateOfDay)
                ->where('user_fid', [Auth::id()])

                ->orWhere('date_due', '=', $dateOfDay)
                ->where('user_fid', [Auth::id()])

                ->orderByRaw('HOUR(time_due), MINUTE(time_due), time_due')
                ->get()
                ->toArray();

            return empty($tasks) ? [] : $tasks;
        }

        //if there are no days passed, by default we fetch all tasks
        $tasks = DB::table('tasks')
            ->where('user_fid', [Auth::id()])
            ->orderByRaw('HOUR(time_due), MINUTE(time_due), time_due')
            ->get()
            ->toArray();

        return empty($tasks) ? [] : $tasks;
    }

    /**
     * Returns a task by ID if it exists
     * If it doesn't exist, it returns empty array
     *
     * @param  int   $taskId
     * @return array
     */
    public function getTaskById(int $taskId): array
    {
        try {
            $task = Task::where('id', $taskId)
                ->where('user_fid', [Auth::id()])
                ->firstOrFail()
                ->toArray();

            return empty($task) ? [] : $task;
        } catch (\Exception $e) {
            // Log the error and return an empty array
            Log::error($e->getMessage());
        }

        return [];
    }

    /**
     * @param  string $chosenDate
     * @return string
     */
    private static function returnCorrectWeekdayIfNoWeekDaysSelected(string $chosenDate): string
    {
        if (!$chosenDate) {
            return redirect()->back()->with("Error: ", "Neither days or date selected! Cannot repeat/set task");
        }

        //get the day of the due date, convert it to a day and turn the field on in the db
        $dueDate = TaskCompletionController::getCarbonDateFromDateString($chosenDate);

        return strtolower($dueDate->format('l'));

    }

    /**
     * Turns a weekdays value to "off" in the tasks database
     *
     * @param  int    $taskId
     * @param  string $weekday lowercase weekday! for example: monday,tuesday ...etc
     * @return void
     */
    public static function setWeekdayValueToOff(int $taskId, string $weekday): void
    {
        if(!$taskId > 0 || !$weekday) {
            redirect()->back()->with("Error", "Insufficient data passed in order to set weekday value off");
        }

        Task::where('id', $taskId)
            ->where('user_fid', [Auth::id()])
            ->update([
                $weekday => "off",
            ],
                [
                    'updated_at' => now()
                ]);
    }

    /**
     * As parameter this function takes an array filled with carbon days of a specific week.
     * It returns the tasks for that specific week along with their completions.
     * @param  array                  $days Contains exactly 7 carbon objects. One for each day of the week from Mon-Sun
     * @return array|RedirectResponse
     */
    private static function mergeTasksWithTheirCompletions(array $days): array|RedirectResponse
    {
        if(empty($days)) {
            return redirect()->back()->with("Error: ", "Carbon object array for week not supplied/is empty");
        }

        // looping through each day of the week
        foreach ($days as $day) {

            // defining carbon properties as variables for quick access
            $dayOfWeek = $day->dayName;
            $dateOfDay = $day->isoFormat('DD/MM/YYYY');

            // fetching the tasks for the currently looping day
            $tasksForThisDay = (new TaskController)->getTasksForDayOfWeek($dayOfWeek, $dateOfDay);

            // did we find any tasks?
            if (!empty($tasksForThisDay)) {
                foreach ($tasksForThisDay as $task) {
                    // we are assigning this attribute to each task, because it will be used later as a comparison against $dateOfDay
                    // both are formatted like DD/MM/YYYY and it must stay this way because this is the database date format
                    $task->dateOfDay = $dateOfDay;

                    // and save it
                    $daysWithTasks[$day->dayName] = $tasksForThisDay;
                }
            }
        }

        if(empty($daysWithTasks)) {
            return [];
        }

        // for each day on which we found tasks
        foreach ($daysWithTasks as $tasks) {

            // loop through all the tasks on that day
            foreach ($tasks as $task) {
                // fetch their completions. a task can have many completions
                $tasksCompletions = TaskCompletionController::getTasksCompletionsByTaskId(
                    $task->id,$task->date_due ?? $task->dateOfDay);

                // loop through all the completions
                foreach ($tasksCompletions as $taskCompletion) {

                    // a task can have multiple completions, but only one completion per task per day
                    // we find the right one here going by date
                    if (strtotime($task->dateOfDay) == strtotime($taskCompletion->date)) {
                        $task->completion = $taskCompletion;

                        // and save them to this final array
                        $tasksWithCompletions[] = $task;
                    }
                }

            }
        }

        return $tasksWithCompletions ?? [];
    }
}

