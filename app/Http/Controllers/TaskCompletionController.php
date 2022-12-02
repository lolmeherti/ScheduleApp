<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskCompletion;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskCompletionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $taskFid
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, int $taskFid)
    {

        //this function can be called either from edit and a task or from creating a brand new one
        //they have a different name in the forms. we check which one it is and update or create with the appropriate one
        $request->datepicker_create ? $date = $request->datepicker_create : $date = $request->datepicker_edit;

        if ($date) {
            $week = $this->getCarbonDateFromDateString($date);
        } else {
            $week = Carbon::now();
        }

        if (isset($request->monday)) {
            $taskDays['monday'] = $week->startOfWeek()->isoFormat('DD/MM/YYYY');//start of week is always monday
        }
        if (isset($request->tuesday)) {
            $taskDays['tuesday'] = $week->startOfWeek()->addDay(1)->isoFormat('DD/MM/YYYY');//add day 1 is tuesday ...etc
        }
        if (isset($request->wednesday)) {
            $taskDays['wednesday'] = $week->startOfWeek()->addDay(2)->isoFormat('DD/MM/YYYY');
        }
        if (isset($request->thursday)) {
            $taskDays['thursday'] = $week->startOfWeek()->addDay(3)->isoFormat('DD/MM/YYYY');
        }
        if (isset($request->friday)) {
            $taskDays['friday'] = $week->startOfWeek()->addDay(4)->isoFormat('DD/MM/YYYY');
        }
        if (isset($request->saturday)) {
            $taskDays['saturday'] = $week->startOfWeek()->addDay(5)->isoFormat('DD/MM/YYYY');
        }
        if (isset($request->sunday)) {
            $taskDays['sunday'] = $week->startOfWeek()->endOfWeek()->isoFormat('DD/MM/YYYY');
        }

        if(isset($taskDays)){
            foreach ($taskDays as $weekDay => $weekDayDate) {

                TaskCompletion::updateOrCreate(
                    [
                        'task_fid' => $taskFid,
                        'date' => $weekDayDate,
                        'completed' => 'off',
                    ],
                    ['updated_at' => now()]
                );
            }
        } else {
            TaskCompletion::updateOrCreate(
                [
                    'task_fid' => $taskFid,
                    'date' => $date,
                    'completed' => 'off',
                ],
                ['updated_at' => now()]
            );
        }
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\TaskCompletion $taskCompletion
     * @return \Illuminate\Http\Response
     */
    public function show(TaskCompletion $taskCompletion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\TaskCompletion $taskCompletion
     * @return \Illuminate\Http\Response
     */
    public function edit(TaskCompletion $taskCompletion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\TaskCompletion $taskCompletion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TaskCompletion $taskCompletion)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\TaskCompletion $taskCompletion
     * @return \Illuminate\Http\Response
     */
    public function destroy(TaskCompletion $taskCompletion)
    {
        //
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
            $day = $dateDue[0];
            $month = $dateDue[1];
            $year = $dateDue[2];

            return Carbon::createFromDate($year, $month, $day, 'Europe/Vienna');
        }
        return Carbon::now();
    }

    /**
     * @param array $tasks
     * @return array
     */
    public static function getTasksCompletionsByTaskId($taskId): array
    {
        $result = array();

        if (isset($taskId)) {
                $result = DB::table('task_completions')
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
    public function completeTaskById(Request $request) : JsonResponse
    {
        //filter var with FILTER_VALIDATE_BOOLEAN flag to make sure its a boolean value
        filter_var($request->completed,FILTER_VALIDATE_BOOLEAN) ? $completed = "on" : $completed = "off";

        $updated = TaskCompletion::where('id', $request->id)
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
    public function deleteTaskCompletionTaskById(Request $request) : JsonResponse
    {

        $taskCompletionTaskFid = TaskCompletion::where('id', $request->completionId)->value('task_fid');

        //if this is the only completion and its getting deleted, we want to delete the task from the task table before we delete the last completion
        $completionsLeftForTask = TaskCompletion::where('task_fid', $taskCompletionTaskFid)->get()->toArray();

        //if we are deleting the very last completion
        if(count($completionsLeftForTask) == 1){
            Task::destroy($taskCompletionTaskFid);
        }

        $deleted = TaskCompletion::where('id', $request->completionId)
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
        $request->datepicker_create ? $dueDateOfTask = $request->datepicker_create : $dueDateOfTask = $request->datepicker_edit;

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
            if(strtotime($mondayDate) >= strtotime($dueDateOfTask)){
                $taskDays['monday'] = $mondayDate;
            }

        }

        if (isset($request->tuesday)) {

            $tuesdayDate = $week->startOfWeek()->addDay(1)->isoFormat('DD/MM/YYYY');//add day 1 is tuesday ...etc
            if(strtotime($tuesdayDate) >= strtotime($dueDateOfTask)){
                $taskDays['tuesday'] = $tuesdayDate;
            }

        }

        if (isset($request->wednesday)) {

            $wednesdayDate = $week->startOfWeek()->addDay(2)->isoFormat('DD/MM/YYYY');
            if(strtotime($wednesdayDate) >= strtotime($dueDateOfTask)){
                $taskDays['wednesday'] = $wednesdayDate;
            }

        }

        if (isset($request->thursday)) {

            $thursdayDate = $week->startOfWeek()->addDay(3)->isoFormat('DD/MM/YYYY');
            if(strtotime($thursdayDate) >= strtotime($dueDateOfTask)){
                $taskDays['thursday'] = $thursdayDate;
            }

        }

        if (isset($request->friday)) {

            $fridayDate = $week->startOfWeek()->addDay(4)->isoFormat('DD/MM/YYYY');
            if(strtotime($fridayDate) >= strtotime($dueDateOfTask)){
                $taskDays['friday'] = $fridayDate;
            }

        }

        if (isset($request->saturday)) {

            $saturdayDate = $week->startOfWeek()->addDay(5)->isoFormat('DD/MM/YYYY');
            if(strtotime($saturdayDate) >= strtotime($dueDateOfTask)){
                $taskDays['saturday'] = $saturdayDate;
            }

        }

        if (isset($request->sunday)) {

            $sundayDate = $week->endOfWeek()->isoFormat('DD/MM/YYYY');
            if(strtotime($sundayDate) >= strtotime($dueDateOfTask)){
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
    public static function removeCompletionsFromUnselectedDays(array $selectedDays, string $dueDate, int $taskFid) : void
    {
        if(count($selectedDays) > 0)
        {
            //we are getting back the week during which the task is set
            $weekdaysInWeekOfTask = TaskController::getEntireWeekFromCarbonDateString($dueDate);

            //foreach weekday in the week of task
            foreach($weekdaysInWeekOfTask as $day) {

                $currentDay = strtolower($day->format('l'));
                if(!array_key_exists($currentDay,$selectedDays)) { //check that the day isn't selected
                    //if we find any days that were unselected,
                    // remove those completions from task_completions table and
                    TaskCompletion::where('task_fid', $taskFid)
                        ->where('date', $day->isoFormat('DD/MM/YYYY'))
                        ->delete();

                    // set the weekdays to "off" in tasks table.
                    TaskController::setWeekdayValueToOff($taskFid, $currentDay);
                }
            }
        }
    }

}
