<style>
    .container {
        position: relative;
        display: flex; /* or inline-flex */
        flex-wrap: wrap;
        gap: 25px;
        margin-top: 30px;
        height: 1200px;
        overflow: auto;
    }

    a {
        cursor: pointer;
    }

    .special-label {
        cursor: pointer;
    }


</style>

<x-app-layout>
    @include('task.edit')
    @include('task.create')

    @php
        $carbonNow = \Carbon\Carbon::now();
        $todayDayName = \Carbon\Carbon::now()->format('l');
        $todayDate = $carbonNow->format('d/m/Y');
        $thisWeek = $carbonNow->startOfWeek();
    @endphp

    {{--timeframe navigation--}}
    <form action="{{route('list')}}" id="custom_week" name="custom_week">
        <div class="position:relative" style="margin: 8 auto; padding-right:4.5%; float: none; width:25%;">
            <input class="form-control text-center bg-dark text-white border border-warning rounded" id="selected_week"
                   name="selected_week" value="{{$dateForWeekSelect}}"
                   onclick="showSelectedWeek()"
            >
        </div>
    </form>


    <div class="container" style="margin: 0 auto; float: none;">

        @foreach($days as $day)

            @php
                $todayDayName == $day->dayName ? $border = "border border-warning" : $border = "border border-white"
            @endphp

            {{-- the controller gives us an array of carbon days to render --}}
            <div class="card" style="width: 30%;">
                <div class="card-header text-center text-white bg-dark mb-0.5 align {{$border}}"
                     @if($todayDayName == $day->dayName)
                         class="border border-warning"
                     @endif
                     style="font-size:20px; padding-bottom:-5%; font-weight:bold;">
                    @if($todayDayName == $day->dayName)
                        {{'Today'}}
                    @else
                        {{$day->dayName}} <br>
                    @endif
                    <div style="font-size:15px">
                        {{$day->isoFormat('Do MMM')}}
                    </div>
                </div>

                <table class="table table-responsive table-dark table-sm text-center" style="margin-bottom:8px;">
                    <thead>
                    <tr>
                        <th scope="col">Done</th>
                        <th scope="col">Time</th>
                        <th scope="col">Task</th>
                        <th scope="col">Delete</th>
                    </tr>
                    </thead>

                    @foreach($tasks as $task)

                        {{-- each carbon day can have multiple tasks --}}
                        @php

                                //carbon day names start with uppercase letters. for example "Monday, Tuesday.."
                                //in the database, day names are all lowercase. they need to match "monday, tuesday.."
                                $lowerCaseDayName = strtolower($day->dayName); //we convert the carbon day names to lower case

                                //check if the day name matches our current day
                                $task->$lowerCaseDayName == "on" ? $showTask = true : $showTask = false;

                                //these variables are merely to aid readability of the code
                                $tasksDueDate = $task->date_due;
                                $currentlyRenderedDay = $day->isoFormat('DD/MM/YYYY');
                                $taskOnRepeat = $task->repeating;

                                //if a due date is set
                                //only display the task when the due date is between start and end of week
                                //otherwise assume that it is a repeating task

                                if (!isset($tasksDueDate)) {
                                    $tasksDueDate = '';
                                }

                            $tasksDueDateWeek = DateTime::createFromFormat('d/m/Y', $tasksDueDate);

                            $tasksDueDateWeek = Carbon\Carbon::Parse($tasksDueDateWeek);
                            $startOfDueDateWeek = $tasksDueDateWeek->startOfWeek();

                            $startOfDueDateWeek = \Carbon\Carbon::parse($startOfDueDateWeek);
                            $endOfDueDateWeek = $tasksDueDateWeek->endOfWeek();

                        @endphp

                        {{-- if showTask is true
                            and the date due lays in between
                            the start of the tasks week and the end of the tasks week --}}
                        {{-- or if the task is repeating --}}
                        {{-- show the task on the current day --}}
                        @if(($showTask || $currentlyRenderedDay == $tasksDueDate) && (($tasksDueDateWeek >= $startOfDueDateWeek && $tasksDueDateWeek <= $endOfDueDateWeek) || $taskOnRepeat))

                                    {{--completions are recorded in a separate table. we are looking to see if the current task has a matching
                                        completion as well, if yes, we are rendering checkboxes for each instance of the task

                                        the reason for this is: the task is a single entity, separated from all the instances of it
                                        the task can be edited as a single entity and it will be edited across the board, it is perceived as the same task
                                        however the same task can occur on multiple days, for example: doing chores
                                        the task is the same, however each day is a different instance of it

                                thats why completions exist separately and must be displayed in an individual way apart from task entities
                            --}}

                            @php
                                $taskCompletions = (App\Http\Controllers\TaskCompletionController::getTasksCompletionsByTaskId($task->id));
                            @endphp

                            @foreach($taskCompletions as $completion)


                                <tbody>

                                @if($currentlyRenderedDay == $completion->date && $task->id == $completion->task_fid)

                                    <tr id="task{{$completion->id}}">

                                        <td>
                                            <input type="checkbox" class="form-check-input bg-dark border border-white"
                                                   name="complete{{$completion->id}}"
                                                   id="complete{{$completion->id}}"
                                                   @if($completion->completed == 'on')
                                                       {{'checked'}}
                                                   @endif onclick="completeTask({{ $completion->id }})">
                                        </td>

                                        <td>
                                            {{$task->time_due}}
                                        </td>

                                        <td>
                                            <label class="special-label" for="exampleCheck1"
                                                   onclick="openEditForm({{$task->id}})">{{$task->description}}</label>
                                        </td>

                                        <td>
                                            <button type="button" onclick="deleteTask({{$completion->id}})"
                                                    class="btn btn-danger btn-sm pull-right"
                                                    style="color:red" ;
                                                    id="delete{{$completion->id}}">Delete
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                                @endforeach
                                @endif
                                @endforeach
                                </tbody>
                </table>
                <hr>
                <button type="button" style="margin-top:5px;"
                        onclick="openCreateForm({{strtolower($day->dayName)}})"
                        class="btn btn-outline-dark btn-sm">New
                </button>

            </div>
        @endforeach
    </div>


</x-app-layout>

<script src="https://code.jquery.com/jquery-3.6.0.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
<link rel="stylesheet" href="https://ajax.aspnetcdn.com/ajax/jquery.ui/1.12.1/themes/ui-darkness/jquery-ui.css">


<script>

    //open & close create form scripts
    function openCreateForm(dayName) {
        //check whichever day the created button was clicked on
        //if the plus button is clicked under monday, monday will be checked by default
        $(dayName).prop("checked", true);

        //show the create form
        document.getElementById("createTaskForm").style.display = "block";
    }

    function closeCreateForm() {
        document.getElementById("create_task").reset();
        document.getElementById("createTaskForm").style.display = "none";
    }

    //open & close create form scripts

    //open & close edit form scripts
    function openEditForm(taskId) {
        //request data for this form by ajax
        fillTaskEditForm(taskId);
        document.getElementById("editTaskForm").style.display = "block";
    }

    function closeEditForm() {
        document.getElementById("editTaskForm").style.display = "none";
    }

    //open & close edit form scripts

    //complete a task script
    function completeTask(taskCompletionId) {

        var completed = {completed: $('#complete' + taskCompletionId).prop("checked")};

        $.ajax({
            type: "GET",
            url: '/list/create_completions/' + taskCompletionId,
            data: completed,
            success: function (response) {
                if (response.status == 404) {
                    //TODO: display error message
                }
            }
        });
    }

    //complete a task script

    //delete a task script
    function deleteTask(taskCompletionId) {

        if (taskCompletionId) {
            $('#task' + taskCompletionId).remove();

            var postData = {
                'completionId': taskCompletionId
            }

            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                type: "POST",
                url: '/list/delete_completion/',
                data: postData,
                success: function (response) {
                    if (response.status == 404) {
                        //TODO: display error message
                    }
                }
            });
        }
    }

    //delete a task script

    //week selection scripts
    $(function () {
        $("#selected_week").datepicker({
            minDate: 0,
            dateFormat: 'dd/mm/yy'
        });
    });

    function showSelectedWeek() {

        $('#selected_week').datepicker().on('change', function () {

            document.getElementById("custom_week").submit();
        });
    }

    //week selection script


</script>

