<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return View
     */
    public function index(Request $request) : View
    {
        if ($request->has('selected_week')) {// this comes from the date-picker on index
            $selectedWeek = TaskCompletionController::getCarbonDateFromDateString($request->input('selected_week'));

            $startOfWeek = $selectedWeek->startOfWeek()->format('d-m-Y H:i');
            $endOfWeek = $selectedWeek->EndOfWeek()->format('d-m-Y H:i');

            $dateForWeekSelect = $request->input('selected_week');
        } else {
            $carbonNow = Carbon::now();
            $dateForWeekSelect = $carbonNow->format('d/m/Y');
        }

        //by default, this function returns the current week
        //the function returns an array with all carbon day objects
        //between the two dates
        $days = TaskController::getAllDaysBetweenTwoDates($startOfWeek ?? "", $endOfWeek ?? "");

        $tasksWithCompletions = $this->mergeTasksWithTheirCompletions($days);

        return view('task.list', compact('days', 'tasksWithCompletions', 'dateForWeekSelect'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return void
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {

        //tasks table section
        $request->validate(
            [
                'title' => ['required', 'string', 'max:500', 'unique:tasks'],
                'description' => ['string', 'max:1000', 'unique:tasks'],
                'datepicker_create' => ['required_without:repeating'],
                'repeating' => ['required_without_all:monday,tuesday,wednesday,thursday,friday,saturday,sunday,datepicker_create, nullable']
            ]);

        $validDaysSelected = TaskCompletionController::getTaskDays($request);

        //either we know which day the task is for
        //or the task is repeating
        if (count($validDaysSelected) > 0 || $request->input('repeating') == "on" || $request->input('datepicker_create') !== null) {
            $insertTaskId = DB::table('tasks')->insertGetId([
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'repeating' => $request->input('repeating') ?? "off",
                'monday' => $request->input('monday') ?? "off",
                'tuesday' => $request->input('tuesday') ?? "off",
                'wednesday' => $request->input('wednesday') ?? "off",
                'thursday' => $request->input('thursday') ?? "off",
                'friday' => $request->input('friday') ?? "off",
                'saturday' => $request->input('saturday') ?? "off",
                'sunday' => $request->input('sunday') ?? "off",
                'user_fid' => Auth::id(),
                'time_due' => $request->input('timepicker') ?? null,
                'date_due' => $request->input('datepicker_create') ?? null
            ]);
            //task table section


            //if there was a date selected, we need to check whether there is also a weekday selected
            if ($request->input('datepicker_create')) {
                TaskController::updateCorrectWeekdayIfNoWeekDaysSelected($request, $insertTaskId); //if there isn't a weekday selected, tick the correct weekday

            }

            //task completion table section
            (new TaskCompletionController)->store($request, $insertTaskId);
            //task completion table section
        }

        if (isset($insertTaskId)) {
            return redirect()->back()->with('success', 'task created successfully!');
        }

            return redirect()->back()->with('error', 'something went wrong!');
    }

    /**
     * Display the specified resource.
     * This function ends up being called by Ajax from the edit view
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $editFormData = $this->getTaskById($request->id);
        if ($editFormData) {
            return response()->json([
                'status' => 200,
                'task' => $editFormData
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Task not found!',
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Task $task
     * @return RedirectResponse
     */
    public function edit(Request $request) : RedirectResponse
    {
        $request->validate(
            [
                'title' => ['required', 'string', 'max:500'],
                'description' => ['string', 'max:1000'],
                'datepicker_edit' => 'required_without:repeating',
                'repeating' => 'required_without:datepicker_edit',
            ]);

        if($request->user_fid > 0){
           $user_fid = $request->user_fid;
        } else {
            $user_fid = Auth::id();
        }

        Task::where('id', $request->id)
            ->update([
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'repeating' => $request->input('repeating'),
                'monday' => $request->input('monday'),
                'tuesday' => $request->input('tuesday'),
                'wednesday' => $request->input('wednesday'),
                'thursday' => $request->input('thursday'),
                'friday' => $request->input('friday'),
                'saturday' => $request->input('saturday'),
                'sunday' => $request->input('sunday'),
                'user_fid' =>$user_fid,
                'time_due' => $request->input('timepicker_edit'),
                'date_due' => $request->input('datepicker_edit'),
                'updated_at' => now(),
            ]);

        //task completion table section
        (new TaskCompletionController)->store($request, $request->id);

        //task completion table section

        return redirect()->back()->with('success', 'task created successfully!');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Task $task
     * @return void
     */
    public function update(Request $request, Task $task) : void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * This function only gets called from TaskCompletionController by design
     * We are already making sure that a taskId is passed and that it is a valid Integer
     * @param int $taskId
     * @return void
     */
    public function destroy(int $taskId): void
    {
        Task::where('id', $taskId)->delete();
    }

    /**
     * Returns all days between two specific dates.
     * If parameters are not set, it returns all days in the current week
     * @param string $startDate
     * @param string $endDate
     * @return array{}
     */
    public static function getAllDaysBetweenTwoDates(string $startDate = "", string $endDate = ""): array
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
     * @param string $dayOfWeek
     * @param string $dateOfDay
     * @return array
     */
    public static function getTasksForDayOfWeek(string $dayOfWeek = "", string $dateOfDay = "") : array
    {
        $result = array();

        if (strlen($dayOfWeek) > 0) {
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

            if (count($tasks) > 0) {
                $result = $tasks;
            }

        } else {
            //if there are no days passed, by default we fetch all tasks
            $tasks = DB::table('tasks')
                ->where('user_fid', [Auth::id()])
                ->orderByRaw('HOUR(time_due), MINUTE(time_due), time_due')
                ->get()
                ->toArray();
            $result = $tasks;
        }

        return $result;
    }

    /**
     * Returns a task by Id if it exists
     * If it doesn't exist, it returns empty array
     * @param int $taskId
     * @return array
     */
    public static function getTaskById(int $taskId): array
    {
        try {
            $task = Task::where('id', $taskId)
                ->where('user_fid', [Auth::id()])
                ->firstOrFail()
                ->toArray();

            if ($task) {
                return $task;
            }
        } catch (\Exception $e) {
            // Log the error and return an empty array
            Log::error($e->getMessage());
        }

        return [];
    }

    /**
     * @param Request $request
     * @param int $taskId
     * @return void
     */
    public static function updateCorrectWeekdayIfNoWeekDaysSelected(Request $request, int $taskId): void
    {
        if ($request->input('datepicker_create')) {
            //if there is a date due but no day of week picked, automatically check the correct day
            $weekDays = TaskCompletionController::getCarbonDateFromDateString($request->input('datepicker_create'));

            $usersSelectedWeekDays = array();

            foreach ($weekDays as $weekDay) {
                if (strtolower($request->$weekDay->format('l')) == "on") {
                    $usersSelectedWeekDays[] = $request->$weekDay->format('l');
                }
            }

            //if the user selected none of the weekdays
            if (!$usersSelectedWeekDays) {
                //get the day of the due date, convert it to a day and turn the field on in the db
                $dueDate = TaskCompletionController::getCarbonDateFromDateString($request->input('datepicker_create'));
                $dueDay = $dueDate->format('l');

                //if we get this far, it means none of the days have been selected but a due date has been submitted
                //we tick the due dates weekday on in the background

                Task::where('id', $taskId)
                    ->where('user_fid', [Auth::id()])
                    ->update([
                        $dueDay => 'on',
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    /**
     * This function returns a week of carbon objects from a date string
     * For example 2/12/2022 would return an array of 7 carbon dates
     * starting from that weeks monday to sunday.
     * @param string $dateString //format of this string is dd/mm/yyyy
     * @return array of CarbonPeriod
     */
    public static function getEntireWeekFromCarbonDateString(string $dateString): array
    {
        $carbonDate = TaskCompletionController::getCarbonDateFromDateString($dateString);
        $startOfWeek = $carbonDate->startOfWeek()->format('d-m-Y H:i');;
        $endOfWeek = $carbonDate->endOfWeek()->format('d-m-Y H:i');;

        return CarbonPeriod::create($startOfWeek, $endOfWeek)->toArray();
    }

    /**
     * Turns a weekdays value to "off" in the tasks database
     * @param int $taskId
     * @param string $weekday lowercase weekday! for example: monday,tuesday ...etc
     * @return void
     */
    public static function setWeekdayValueToOff(int $taskId, string $weekday): void
    {
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
     * @param int
     * @return array
     */
    public static function getAllTasksForLoggedInUser() : array
    {

        $tasks = Task::where('user_fid', Auth::id())
            ->firstOrFail()
            ->get()
            ->toArray();

        if ($tasks) {
            return $tasks;
        }

        return [];
    }

    /**
     * As parameter this function takes an array filled with carbon days of a specific week.
     * It returns the tasks for that specific week along with their completions.
     * @param array $days
     * @return array
     */
    public function mergeTasksWithTheirCompletions(array $days): array
    {
        $daysWithTheirRespectiveTasks = array();

        // looping through each day of the week
        foreach ($days as $key => $day) {

            // defining carbon properties as variables for quick access
            $dayOfWeek = $day->dayName;
            $dateOfDay = $day->isoFormat('DD/MM/YYYY');

            // fetching the tasks for the currently looping day
            $tasksForThisDay = TaskController::getTasksForDayOfWeek($dayOfWeek, $dateOfDay);

            $tasksFound = count($tasksForThisDay);

            // did we find any tasks?
            if ($tasksFound > 0) {

                for($i=0;$i<$tasksFound;$i++){
                    // at this point, we want to add the date to the task object
                    $tasksForThisDay[$i]->dateOfDay = $dateOfDay;

                    // and save it
                    $daysWithTheirRespectiveTasks[$day->dayName] = $tasksForThisDay;
                }
            }
        }

        $tasksWithTheirCompletions = array();

        // for each day on which we found tasks
        foreach ($daysWithTheirRespectiveTasks as $dayName => $tasks) {

            // loop through all the tasks on that day
            foreach ($tasks as $key => $task) {

                // fetch their completions. a task can have many completions
                $tasksCompletions = TaskCompletionController::getTasksCompletionsByTaskId($task->id);

                // if there are completions found for this task
                if (!empty($tasksCompletions)) {

                    // loop through all the completions
                    foreach ($tasksCompletions as $completionKey => $taskCompletion) {

                        // a task can have multiple completions, but only one completion per task per day
                        // we find the right one here going by date
                        if (strtotime($task->dateOfDay) == strtotime($taskCompletion->date)) {
                            $task->completion = $taskCompletion;

                            // and save them to this final array
                            $tasksWithTheirCompletions[] = $task;
                        }
                    }
                }
            }
        }
        return $tasksWithTheirCompletions;
    }
}

