<?php

namespace App\Http\Controllers;

use App\Models\TaskCompletion;
use Carbon\Carbon;
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

        if(isset($request->datepicker_create)){
            $week = $this->getCarbonWeekFromDateDue($request->datepicker_create);
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

            foreach ($taskDays as $day => $date) {
                DB::table('task_completions')->insert([
                    'task_fid' => $taskFid,
                    'date' => $date,
                    'completed' => 'off',
                    'created_at' => now()
                ]);
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
     * Returns Start&End of the week of specific date
     *
     * This param refers to date_due column in the database
     * The required format is DD/MM/YYYY for the parameter
     * @param string $dateDue
     *
     * Returns current carbon date by default
     * @return Carbon
     */
    public static function getCarbonWeekFromDateDue(string $dateDue): Carbon
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
    public static function getTasksCompletions($tasks) : array {
        $result = array();

        if(isset($tasks))
        {
            foreach($tasks as $task) {
                $result = DB::table('task_completions')
                    ->where('task_fid', $task->id)
                    ->get()
                    ->toArray();
            }
        }

        return $result;
    }
}