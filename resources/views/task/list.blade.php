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

    .list-group {
        margin-left: 5px;
    }

    .special-label {
        cursor: pointer;
    }
</style>

<x-app-layout>
    <div class="container">
        @foreach($days as $day) {{-- the controller gives us an array of carbon days to render --}}
            <div class="card" style="width: 20rem;">
                <div class="card-header text-center text-white bg-dark mb-3 align" style="font-size:15px;">
                    {{$day->dayName}}
                    {{$day->day}}
                    {{$day->format('F')}}
                </div>
                <ul class="list-group list-group-flush">
                    <li>
                        <div class="form-check">
                            @foreach($tasks as $task) {{-- each carbon day can have multiple tasks --}}
                                <?php

                                //carbon day names start with uppercase letters. for example "Monday, Tuesday.."
                                //in the database, day names are all lowercase. they need to match "monday, tuesday.."
                               $lowerCaseDayName = strtolower($day->dayName); //we convert the carbon day names to lower case

                                //check if the day name matches our current day
                                $task->$lowerCaseDayName == "on" ? $showTask = true : $showTask = false;

                                //these variables are merely to aid readability of the code
                                $tasksDueDate = $task->date_due;
                                $currentlyRenderedDay = $day->isoFormat('DD/MM/YYYY');
                                $taskOnRepeat = $task->repeating;
                                ?>

                                {{-- if showTask is true and the date due lays in the future --}}
                                {{-- or if the task is repeating --}}
                                {{-- show the task on the current day --}}

                                @if($showTask && ($tasksDueDate >= $currentlyRenderedDay || $taskOnRepeat))
                                    <input type="checkbox" class="form-check-input" id="{{$task->id}}">
                                    <label class="form-check-label special-label" for="exampleCheck1"
                                           onclick="openEditForm({{$task->id}})">{{$task->description}}</label>
                                <hr>
                                @endif

                            @endforeach
                        </div>

                    </li>
                </ul>
            </div>
        @endforeach
        @include('task.edit')
        @include('task.create')
    </div>

    <button type="button" class="btn btn-outline-secondary" onclick="openCreateForm()">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus"
             viewBox="0 0 16 16">
            <path
                d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"></path>
        </svg>
        <span class="visually-hidden">Button</span>
    </button>


</x-app-layout>

<script src="https://code.jquery.com/jquery-3.6.0.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">


<script>


    function openCreateForm() {
        document.getElementById("createTaskForm").style.display = "block";
    }

    function closeCreateForm() {
        document.getElementById("createTaskForm").style.display = "none";
    }

    function openEditForm(taskId) {
        //request data for this form by ajax
        fillTaskEditForm(taskId);
        document.getElementById("editTaskForm").style.display = "block";
    }

    function closeEditForm() {
        document.getElementById("editTaskForm").style.display = "none";
    }

</script>

