<?php

namespace App\Http\Controllers;

use Carbon\CarbonPeriod;
use DateTime;
use App\{
    Models\Task,
    Models\TaskCompletion
};
use Carbon\Carbon;
use Illuminate\{Http\JsonResponse,
    Http\RedirectResponse,
    Http\Request,
    Http\Response,
    Support\Facades\Auth,
    Support\Facades\DB};

class TaskCompletionController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  Request          $request
     * @param  int              $taskFid
     * @return RedirectResponse
     */
    public function store(Request $request, int $taskFid): RedirectResponse
    {
        if($taskFid<=0) {
            return redirect()->back()->with("Error", "Insufficient data passed to store task completions!");
        }

        //this function can be called either from edit and a task or from creating a brand new one
        //they have a different name in the forms. we check which one it is and update or create with the appropriate one
        $dueDateOfTask = $request->input('datepicker_create') ?:  $request->input('datepicker_edit');

        //getting the selected days from the edit/create form
        $taskDays            = $this->getTaskDays($request);
        $authenticatedUserId = Auth::id();

        $repeatingTask = $request->input('repeating');

        if(empty($taskDays)) {
            $date = TaskCompletionController::getCarbonDateFromDateString($dueDateOfTask);

            $taskDays = [strtolower($date->format("l")) => $dueDateOfTask];
        }

        foreach ($taskDays as $weekDayDate) {

            $weekDateTime = DateTime::createFromFormat('d/m/Y', $weekDayDate);

            if(!$dueDateOfTask) {
                $todayDateTime = new DateTime();
                $todayDateTime->setTime(0, 0, 0);

                $nextOccurrenceOfDay = clone $weekDateTime;
                $weekDateTime > $todayDateTime ?: $nextOccurrenceOfDay->modify("+1 Week");
                $formattedNextOccurrence = $nextOccurrenceOfDay->format("d/m/Y");

                $this->createTaskCompletion($taskFid, $authenticatedUserId, $formattedNextOccurrence, $taskDays);

                continue;
            }

            $dueDayUnix = DateTime::createFromFormat('d/m/Y', $dueDateOfTask)->getTimestamp();

            if(!$repeatingTask) {
                $this->createTaskCompletion($taskFid, $authenticatedUserId, $dueDateOfTask);
            }

            if (strtotime($weekDateTime->getTimestamp()) >= strtotime($dueDayUnix) && $repeatingTask) {
                $this->createTaskCompletion($taskFid, $authenticatedUserId, $weekDayDate, $taskDays);
            }
        }

        return redirect()->back();
    }

    /**
     * @param  int    $taskFid  Refers to the task's db ID
     * @param  int    $userFid  Refers to the user's db ID
     * @param  string $date     Refers to the date of the task
     * @param  array  $taskDays Optional Arr - Refers to the days which have tasks (Used for repeating tasks)
     * @return void
     *
     * Inserts task completion based off task_fid, user_fid and date
     */
    private function createTaskCompletion(int $taskFid, int $userFid, string $date, array $taskDays = []): void
    {
        TaskCompletion::updateOrCreate(
            [
                'task_fid'  => $taskFid,
                'user_fid'  => $userFid,
                'date'      => $date,
                'completed' => 'off',
            ],
            ['updated_at' => now()]
        );

        if(!empty($taskDays)) {
            $this->removeCompletionsFromUnselectedDays($taskDays, $date, $taskFid);
        }
    }

    /**
     * This param refers to date_due column in the database
     * The required format is DD/MM/YYYY for the parameter
     * @param string $dateDue
     *
     * Returns a Carbon Date Object from parameter string or else it returns a Carbon Date Object of today.
     * @return Carbon|null
     */
    public static function getCarbonDateFromDateString(string $dateDue): Carbon|null
    {
        if (!$dateDue) {
            return Carbon::now();
        }

        //we are exploding a date format such as 15/11/2022 at the / separator
        //explode makes an array of all values separated by /
        //the format is dd/mm/yyyy, so the first element contains day, second contains month and third contains year.

        $dateDue = explode('/', $dateDue);

        return count($dateDue) == 3 ?
            Carbon::createFromDate($dateDue[2], $dateDue[1], $dateDue[0], 'Europe/Vienna') : null;

    }

    /**
     * @param  int                    $taskId
     * @param  string                 $date
     * @return array|RedirectResponse
     */
    public static function getTasksCompletionsByTaskId(int $taskId, string $date): array|RedirectResponse
    {
        if(!$date || $taskId <= 0) {
            return redirect()->back()->with(
                "Error: ",
                "Incorrect task ID or date provided. Cannot fetch task completions!"
            );
        }

        return DB::table('task_completions')
            ->where('user_fid', [Auth::id()])
            ->where('task_fid', $taskId)
            ->where("date", $date)
            ->get()
            ->toArray();
    }

    /**
     * @param  Request                       $request
     * @return JsonResponse|RedirectResponse
     */
    public function completeTaskById(Request $request): JsonResponse|RedirectResponse
    {
        $validatedReq = $request->validate(
            [
                "completed" => "required|string",
            ]
        );

        $validatedReq["completed"] = $validatedReq["completed"] == "true" ? "on" : "off";

        if (empty($validatedReq)) {
            return redirect()->back()->with('error', 'Error validating completion request');
        }

        $updated = TaskCompletion::where('id', $request->id)
            ->where('user_fid', [Auth::id()])
            ->update([
                'completed' => $validatedReq["completed"]]);

        return $updated ?
            response()->json(['status' => 200]) : response()->json(['status' => 404, 'message' => 'Task not found!']);
    }


    /**
     * @param  Request      $request
     * @return JsonResponse
     */
    public function deleteTaskCompletionTaskById(Request $request): JsonResponse
    {
        $taskCompletionTaskFid = TaskCompletion::where('id', $request->completionId)
            ->where('user_fid', [Auth::id()])
            ->value('task_fid');

        //checking how many completions are left
        //if this is the only completion and its getting deleted, we want to delete the task from the task table before we delete the last completion
        $completionsLeftForTask = TaskCompletion::where('task_fid', $taskCompletionTaskFid)
            ->where('user_fid', [Auth::id()])
            ->get()
            ->toArray();

        count($completionsLeftForTask) !== 1 || Task::destroy($taskCompletionTaskFid);

        //when a completion is deleted, edit the task and set the day value to off
        //first we get the date string from completions_table
        $currentDay = TaskCompletion::where('id', $request->completionId)->value('date');

        //make a carbon object out of it
        $currentDayCarbonObject = TaskCompletionController::getCarbonDateFromDateString($currentDay);

        //get the completion's weekday in lowercase
        $currentWeekDay = strtolower($currentDayCarbonObject->format('l'));

        //if the task is not repeating
        $isTaskRepeating = DB::table('tasks')->where('id', $taskCompletionTaskFid)->value('repeating');

        $isTaskRepeating !== "off" || TaskController::setWeekdayValueToOff($taskCompletionTaskFid, $currentWeekDay);

        $deleted = TaskCompletion::where('id', $request->completionId)
            ->where('user_fid', [Auth::id()])
            ->delete();

        return $deleted ?
            response()->json(['status' => 200]) : response()->json(['status' => 404, 'message' => 'Task not found!']);
    }

    /**
     * @param  Request                $request
     * @return array|RedirectResponse
     */
    private function getTaskDays(Request $request): array|RedirectResponse
    {
        //this function can be called either from edit and a task or from creating a brand new one
        //they have a different name in the forms. we check which one it is and update or create with the appropriate one

        $dateChosen    = $request->input("datepicker_create") ?? $request->input("datepicker_edit");
        $dueDateOfTask = TaskCompletionController::getCarbonDateFromDateString($dateChosen ?? "")
            ->isoFormat('DD/MM/YYYY');


        $week = TaskCompletionController::getCarbonDateFromDateString($dueDateOfTask) ?? Carbon::now();

        $days = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"];

        foreach ($days as $index => $day) {
            if(!isset($request->$day)) {
                continue;
            }

            $date = $this->formatWeekDay($week, $index, $day);

            strtotime($date) < strtotime($dueDateOfTask) || $taskDays[$day] = $date;
        }

        return $taskDays ?? [];
    }

    /**
     * @param  Carbon                  $week           Refers to the week related to the day being formatted
     * @param  int                     $Increment      Refers to how many days need to be added to progress onto the next weekday
     * @param  string                  $day            Refers to the day being formatted
     * @return string|RedirectResponse                 Returns formatted day of the week
     */
    private function formatWeekDay(Carbon $week, int $Increment, string $day): string|RedirectResponse
    {
        if($Increment < 0 || !$day) {
            return redirect()->back()->with("error", "Issue formatting weekday!");
        }

        return $Increment > 0 ? $week->startOfWeek()->addDay($Increment)->isoFormat('DD/MM/YYYY') :
            $week->startOfWeek()->isoFormat('DD/MM/YYYY');
    }

    /**
     * Takes selected days as parameter.
     * Removes all completions from days which are not present in parameter array.
     * $selectedDays parameter should be a dictionary. Day of the week => date of the day. example: monday => 5/12/2022
     *
     * @param  array                 $selectedDays
     * @param  string                $dueDate
     * @param  int                   $taskFid
     * @return void
     */
    private function removeCompletionsFromUnselectedDays(
        array $selectedDays,
        string $dueDate,
        int $taskFid
    ): void
    {
        if(empty($selectedDays)) {
            redirect()->back()->with("error", "selected days to remove completions from not supplied!");
            return;
        }

        //we are getting back the week during which the task is set
        $weekdaysInWeekOfTask = $this->getEntireWeekFromCarbonDateString($dueDate);

        //foreach weekday in the week of task
        foreach ($weekdaysInWeekOfTask as $day) {
            $currentDay = strtolower($day->format('l'));

            if (!isset($selectedDays[$currentDay])) { //check that the day isn't selected
                //if we find any days that were unselected,
                // remove those completions from task_completions table and
                TaskCompletion::where('task_fid', $taskFid)
                    ->where('user_fid', [Auth::id()])
                    ->where('date', $day->isoFormat('DD/MM/YYYY'))
                    ->delete();

                // set the weekdays to "off" in tasks table.
                TaskController::setWeekdayValueToOff($taskFid, $currentDay);
            }
        }

    }

    /**
     * This function returns a week of carbon objects from a date string
     * For example 2/12/2022 would return an array of 7 carbon dates
     * starting from that weeks monday to sunday.
     *
     * @param  string $dateString //format of this string is dd/mm/yyyy
     * @return array              of CarbonPeriods
     */
    private function getEntireWeekFromCarbonDateString(string $dateString): array
    {
        $carbonDate  = TaskCompletionController::getCarbonDateFromDateString($dateString);
        $startOfWeek = $carbonDate->startOfWeek()->format('d-m-Y H:i');;
        $endOfWeek   = $carbonDate->endOfWeek()->format('d-m-Y H:i');;

        return CarbonPeriod::create($startOfWeek, $endOfWeek)->toArray();
    }

    /**
     * This is a scheduled task. It only runs sunday evening
     * This function inserts all repeating tasks every sunday.
     *
     * @return void
     */

    public function prepareRepeatingTasksForNextWeek(): void
    {
        //we assume this task is only executed on sundays.
        //we make a new carbon object for right today ^
        $carbonDate = Carbon::now()
            ->timezone('Europe/Vienna');
        $nextWeek = $carbonDate->nextWeekday();

        $allRepeatingTasks = Task::all()->where('repeating', "=", "on")
            ->toArray();

        if(empty($allRepeatingTasks)) {
            return;
        }

        $days = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"];

        foreach ($allRepeatingTasks as $repeatingTask) {

            foreach ($days as $index => $day) {
                if($repeatingTask[$day] !== "on") {
                    continue;
                }

                $dateDue = $this->formatWeekDay($nextWeek, $index, $day);

                TaskCompletion::Create(
                    [
                        'task_fid'   => $repeatingTask['id'],
                        'user_fid'   => $repeatingTask['user_fid'],
                        'date'       => $dateDue,
                        'completed'  => 'off',
                        'created_at' => now(),
                    ]);
            }
        }
    }

    /**
     * @param  Request      $request
     * @return JsonResponse
     */
    public static function searchCompletionsByTitle(Request $request) : JsonResponse
    {
        $validatedReq = $request->validate(["search" => "required|string"]);

        $tasks = DB::table('tasks')
        ->where('title','LIKE', '%'.$validatedReq["search"].'%')
        ->where('user_fid','=', Auth::id())
        ->get()
        ->toArray();

        return empty($tasks) ?
            response()->json(['status' => 404, 'message' => 'Tasks not found!']) :
            response()->json(['tasks' => $tasks, 'status' => 200]);
    }

}
