<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        //by default, this function returns the current week
        $daysBetweenDates = $this->getAllDaysBetweenTwoDates();

        return view('task.list')->with('days', $daysBetweenDates);
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function show(Task $task)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function edit(Task $task)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Task $task)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function destroy(Task $task)
    {
        //
    }

    /**
     * Returns all days between two specific dates.
     * If parameters are not set, it returns all days in the current week
     * @param string $startDate
     * @param string $endDate
     * @return array{}
     */
      public function getAllDaysBetweenTwoDates(string $startDate = "", string $endDate = ""): array
      {

          $now = Carbon::now();

          if(!$startDate)
          {
              $startDate = $now->startOfWeek()->format('d-m-Y H:i');
          }

          if(!$endDate)
          {
              $endDate = $now->endOfWeek()->format('d-m-Y H:i');
          }

          return CarbonPeriod::create($startDate, $endDate)->toArray();
      }

}
