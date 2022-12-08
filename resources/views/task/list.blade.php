<style>
    .container {
        position: relative;
        display: flex; /* or inline-flex */
        flex-wrap: wrap;

        gap: 1.5em;
        height: 100em;
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
        //current date info
        $carbonNow = \Carbon\Carbon::now();
        $todayDayName = \Carbon\Carbon::now()->format('l');
        $todayDate = $carbonNow->format('d/m/Y');
        $thisWeek = $carbonNow->startOfWeek();
    @endphp

    {{--timeframe navigation--}}
    <form action="{{route('list')}}" id="custom_week" name="custom_week">
        <div class="position:relative" style="margin: 8 auto; padding-right:4.5%; float: none; width:25%; min-width:300px">
            <input class="form-control text-center bg-dark text-white border border-warning rounded" id="selected_week"
                   name="selected_week" value="{{$dateForWeekSelect}}"
                   onclick="showSelectedWeek()"
            >
        </div>
    </form>

    <div class="container" style="margin: 0 auto; float: none;">

        @foreach($days as $day)

            @php
                //add border to today
                $todayDayName == $day->dayName && $todayDate == $day->format('d/m/Y') ? $border = "border border-warning" : $border = "border border-white";
            @endphp

            {{-- the controller gives us an array of carbon days to render --}}
            <div class="card" style="width: 30%; min-width:300px;">
                <div class="card-header text-center text-white bg-dark mb-0.5 align {{$border}}"
                     @if($todayDayName == $day->dayName)
                         class="border border-warning"
                     @endif
                     style="font-size:1.5em; padding-bottom:-5%; font-weight:bold; margin-bottom:0">
                    @if($todayDayName == $day->dayName && $todayDate == $day->format('d/m/Y'))
                        {{'Today'}}
                    @else
                        {{$day->dayName}} <br>
                    @endif
                    <div style="font-size:0.6em">
                        {{$day->isoFormat('Do MMM Y')}}
                    </div>
                </div>

                <table class="table table-responsive table-dark table-sm text-center table-hover"
                       style="margin-bottom:0.3em; margin-top:0; padding-top:0; max-width:100%">
                    <thead>
                    <tr>
                        <th scope="col">Done</th>
                        <th scope="col">Time</th>
                        <th scope="col">Task</th>
                        <th scope="col">Delete</th>
                    </tr>
                    </thead>
                    @foreach($tasksWithCompletions as $task)

                                <tbody>

                                @if($day->isoFormat('DD/MM/YYYY') == $task->completion->date)

                                    <tr id="task{{$task->completion->id}}">

                                        <td style="padding-top:0.2em;">
                                            <input type="checkbox" class="form-check-input bg-dark border border-white"
                                                   name="complete{{$task->completion->id}}"
                                                   id="complete{{$task->completion->id}}"
                                                   @if($task->completion->completed == 'on')
                                                       {{'checked'}}
                                                   @endif onclick="completeTask({{ $task->completion->id }})">
                                        </td>

                                        <td @if($task->repeating == "off") style="padding-top:0.5em;"@endif>
                                            @if($task->repeating == "on")
                                                <button style="font-size:1.1em;">&#10227;</button>
                                            @endif  {{$task->time_due}}
                                        </td>

                                        <td style="padding-top:0.5em;">
                                            <label class="special-label " for="exampleCheck1"
                                                   onclick="openEditForm({{$task->id}})">{{$task->description}}</label>
                                        </td>

                                        <td>
                                            <button type="button" onclick="deleteTask({{$task->completion->id}})"
                                                    class="btn btn-danger btn-sm pull-right"
                                                    style="color:red";
                                                    id="delete{{$task->completion->id}}">Delete
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                                @endforeach
                                </tbody>
                </table>
                <hr>
                <button type="button" style="margin-top:0.5em;"
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
            dateFormat: 'dd/mm/yy',
        });
    });

    function showSelectedWeek() {
        $('#selected_week').datepicker().on('change', function () {
            document.getElementById("custom_week").submit();
        });
    }

    //week selection script
</script>
