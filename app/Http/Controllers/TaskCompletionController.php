<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskCompletion;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskCompletionController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @param int $taskFid
     * @return RedirectResponse
     */
    public function store(Request $request, int $taskFid): RedirectResponse
    {

        //this function can be called either from edit and a task or from creating a brand new one
        //they have a different name in the forms. we check which one it is and update or create with the appropriate one
        $request->input('datepicker_create') ? $dueDateOfTask = $request->input('datepicker_create') : $dueDateOfTask = $request->input('datepicker_edit');

        //getting the selected days from the edit/create form
        $taskDays = $this->getTaskDays($request);

        $repeatingTask = $request->input('repeating');

        $authenticatedUserId = Auth::id();

        if (count($taskDays) > 0) {
            foreach ($taskDays as $weekDay => $weekDayDate) {

                //only create completions in the past for repeating tasks
                if (strtotime($weekDayDate) >= strtotime($dueDateOfTask) || $repeatingTask) {
                    TaskCompletion::updateOrCreate(
                        [
                            'task_fid' => $taskFid,
                            'user_fid' => $authenticatedUserId,
                            'date' => $weekDayDate,
                        ],
                        ['updated_at' => now()]
                    );
                }
            }
        } else { // in case the user only gives a date and no weekdays are selected
            TaskCompletion::updateOrCreate(
                [
                    'task_fid' => $taskFid,
                    'user_fid' => $authenticatedUserId,
                    'date' => $dueDateOfTask,
                    'completed' => 'off',
                ],
                ['updated_at' => now()]
            );
        }

        if ($dueDateOfTask) {
            TaskCompletionController::removeCompletionsFromUnselectedDays($taskDays, $dueDateOfTask, $taskFid);
        }
        return redirect()->back();
    }

    /**
     * This param refers to date_due column in the database
     * The required format is DD/MM/YYYY for the parameter
     * @param string $dateDue
     *
     * Returns a Carbon Date Object from parameter string or else it returns a Carbon Date Object of today.
     * @return Carbon
     */
    public static function getCarbonDateFromDateString(string $dateDue): Carbon
    {
        //we are exploding a date format such as 15/11/2022 at the / separator
        //explode makes an array of all values separated by /
        //the format is dd/mm/yyyy, so the first element contains day, second contains month and third contains year.
        if ($dateDue) {
            $dateDue = explode('/', $dateDue);
            if(count($dateDue) === 3){ //we need to have exactly 3 array elements, otherwise something is wrong
                $day = $dateDue[0];
                $month = $dateDue[1];
                $year = $dateDue[2];

                return Carbon::createFromDate($year, $month, $day, 'Europe/Vienna');
            }
        }
        return Carbon::now();
    }

    /**
     * @param int $taskId
     * @return array
     */
    public static function getTasksCompletionsByTaskId(int $taskId): array
    {
        $result = array();

        if ($taskId > 0) {
            $result = DB::table('task_completions')
                ->where('user_fid', [Auth::id()])
                ->where('task_fid', $taskId)
                ->get()
                ->toArray();
        }

        return $result;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function completeTaskById(Request $request): JsonResponse
    {
        //filter var with FILTER_VALIDATE_BOOLEAN flag to make sure its a boolean value
        filter_var($request->input('completed'), FILTER_VALIDATE_BOOLEAN) ? $completed = "on" : $completed = "off";

        $updated = TaskCompletion::where('id', $request->id)
            ->where('user_fid', [Auth::id()])
            ->update([
                'completed' => $completed]);

        if ($updated) {
            return response()->json([
                'status' => 200
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Task not found!',
            ]);
        }
    }


    /**
     * @param Request $request
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


        //if we are deleting the very last completion
        if (count($completionsLeftForTask) == 1) {
            //destroy the task
            Task::destroy($taskCompletionTaskFid);
        }

        //when a completion is deleted, edit the task and set the day value to off
        //first we get the date string from completions_table
        $currentDay = TaskCompletion::where('id', $request->completionId)->value('date');

        //make a carbon object out of it
        $currentDayCarbonObject = TaskCompletionController::getCarbonDateFromDateString($currentDay);

        //get the completion's weekday in lowercase
        $currentWeekDay = strtolower($currentDayCarbonObject->format('l'));

        //if the task is not repeating
        $isTaskRepeating = DB::table('tasks')->where('id', $taskCompletionTaskFid)->value('repeating');

        if($isTaskRepeating == "off"){
            //turn the weekday off in task table
            //if the task was repeating, it could still occur on different weeks
            //toggling the day off would erase all completions for that day, even for repeating tasks
            TaskController::setWeekdayValueToOff($taskCompletionTaskFid, $currentWeekDay);
        }

        $deleted = TaskCompletion::where('id', $request->completionId)
            ->where('user_fid', [Auth::id()])
            ->delete();


        if ($deleted) {
            return response()->json([
                'status' => 200
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Task not found!',
            ]);
        }
    }

    /**
     * @param Request $request
     * @return array
     */
    public static function getTaskDays(Request $request): array
    {
        //this function can be called either from edit and a task or from creating a brand new one
        //they have a different name in the forms. we check which one it is and update or create with the appropriate one
        $request->input('datepicker_create') ? $dueDateOfTask = $request->input('datepicker_create') : $dueDateOfTask = $request->input('datepicker_edit');

        if ($dueDateOfTask) {
            $week = TaskCompletionController::getCarbonDateFromDateString($dueDateOfTask);
            //convert to carbon object for later comparisons
            $dueDateOfTask = TaskCompletionController::getCarbonDateFromDateString($dueDateOfTask)->isoFormat('DD/MM/YYYY');
        } else {
            $week = Carbon::now();
        }

        $taskDays = array();

        if (isset($request->monday)) {

            $mondayDate = $week->startOfWeek()->isoFormat('DD/MM/YYYY'); //start of week is always monday
            //we can't create tasks for past dates
            //we check if the tasks date is in the future, if yes, add it to the array
            if (strtotime($mondayDate) >= strtotime($dueDateOfTask)) {
                $taskDays['monday'] = $mondayDate;
            }

        }

        if (isset($request->tuesday)) {

            $tuesdayDate = $week->startOfWeek()->addDay(1)->isoFormat('DD/MM/YYYY');//add day 1 is tuesday ...etc
            if (strtotime($tuesdayDate) >= strtotime($dueDateOfTask)) {
                $taskDays['tuesday'] = $tuesdayDate;
            }

        }

        if (isset($request->wednesday)) {

            $wednesdayDate = $week->startOfWeek()->addDay(2)->isoFormat('DD/MM/YYYY');
            if (strtotime($wednesdayDate) >= strtotime($dueDateOfTask)) {
                $taskDays['wednesday'] = $wednesdayDate;
            }

        }

        if (isset($request->thursday)) {

            $thursdayDate = $week->startOfWeek()->addDay(3)->isoFormat('DD/MM/YYYY');
            if (strtotime($thursdayDate) >= strtotime($dueDateOfTask)) {
                $taskDays['thursday'] = $thursdayDate;
            }

        }

        if (isset($request->friday)) {

            $fridayDate = $week->startOfWeek()->addDay(4)->isoFormat('DD/MM/YYYY');
            if (strtotime($fridayDate) >= strtotime($dueDateOfTask)) {
                $taskDays['friday'] = $fridayDate;
            }

        }

        if (isset($request->saturday)) {

            $saturdayDate = $week->startOfWeek()->addDay(5)->isoFormat('DD/MM/YYYY');
            if (strtotime($saturdayDate) >= strtotime($dueDateOfTask)) {
                $taskDays['saturday'] = $saturdayDate;
            }

        }

        if (isset($request->sunday)) {

            $sundayDate = $week->endOfWeek()->isoFormat('DD/MM/YYYY');
            if (strtotime($sundayDate) >= strtotime($dueDateOfTask)) {
                $taskDays['sunday'] = $sundayDate;
            }
        }

        return $taskDays;
    }

    /**
     * Takes selected days as parameter.
     * Removes all completions from days which are not present in parameter array.
     * $selectedDays parameter should be a dictionary. Day of the week => date of the day. example: monday => 5/12/2022
     * @param array $selectedDays
     * @param string $dueDate
     * @param int $taskFid
     * @return void
     */
    public static function removeCompletionsFromUnselectedDays(array $selectedDays, string $dueDate, int $taskFid): void
    {
        if (count($selectedDays) > 0) {
            //we are getting back the week during which the task is set
            $weekdaysInWeekOfTask = TaskController::getEntireWeekFromCarbonDateString($dueDate);

            //foreach weekday in the week of task
            foreach ($weekdaysInWeekOfTask as $day) {

                $currentDay = strtolower($day->format('l'));
                if (!array_key_exists($currentDay, $selectedDays)) { //check that the day isn't selected
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
    }

    /**
     * This is a scheduled task. It only runs sunday evening
     * This function inserts all repeating tasks every sunday.
     * @return void
     */

    public static function prepareRepeatingTasksForNextWeek(): void
    {
        //we assume this task is only executed on sundays.
        //we make a new carbon object for right today ^
        $carbonDate = Carbon::now()
            ->timezone('Europe/Vienna');
        $nextWeek = $carbonDate->nextWeekday();

        $allRepeatingTasks = Task::all()->where('repeating', "=", "on")
            ->toArray();

        if ($allRepeatingTasks) {

            $taskDays = array();

            foreach ($allRepeatingTasks as $task) {

                if ($task['monday'] == "on") {
                    $taskDays['monday'] = $nextWeek->startOfWeek()->isoFormat('DD/MM/YYYY');
                }

                if ($task['tuesday'] == "on") {
                    $taskDays['tuesday'] = $nextWeek->startOfWeek()->addDay(1)->isoFormat('DD/MM/YYYY');
                }

                if ($task['wednesday'] == "on") {
                    $taskDays['wednesday'] = $nextWeek->startOfWeek()->addDay(2)->isoFormat('DD/MM/YYYY');
                }

                if ($task['thursday'] == "on") {
                    $taskDays['thursday'] = $nextWeek->startOfWeek()->addDay(3)->isoFormat('DD/MM/YYYY');
                }

                if ($task['friday'] == "on") {
                    $taskDays['friday'] = $nextWeek->startOfWeek()->addDay(4)->isoFormat('DD/MM/YYYY');
                }

                if ($task['saturday'] == "on") {
                    $taskDays['saturday'] = $nextWeek->startOfWeek()->addDay(5)->isoFormat('DD/MM/YYYY');
                }

                if ($task['sunday'] == "on") {
                    $taskDays['sunday'] = $nextWeek->endOfWeek()->isoFormat('DD/MM/YYYY');
                }

                foreach ($taskDays as $day => $tasksDateDue) {
                    TaskCompletion::Create(
                        [
                            'task_fid' => $task['id'],
                            'user_fid' => $task['user_fid'],
                            'date' => $tasksDateDue,
                            'completed' => 'off',
                            'created_at'=> now(),
                        ]);
                }
            }
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public static function searchCompletionsByTitle(Request $request) : JsonResponse
    {
        $stringToSearch = $request->input('search');

        $tasks = DB::table('tasks')
        ->where('title','LIKE', '%'.$stringToSearch.'%')
        ->where('user_fid','=', Auth::id())
        ->get()
        ->toArray();

        if(count($tasks) > 0) {
            return response()->json([
                'tasks' => $tasks,
                'status' => 200
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Tasks not found!',
            ]);
        }
    }

}
