<style>
    /* The popup form - hidden by default */
    .form-popup {
        margin-left:15%;
        margin-top:2%;
        float: none;
        width:35%;
        display: none;
        opacity: 0.95;
        min-width:300px;
        position:fixed;
    }

    .form-group {
        padding: 10px;
    }
    .form-check {
        padding-right: 10px;
    }

</style>

<div class="form-popup bg-dark border border-warning text-white" id="createTaskForm">
    <form id="create_task" name="create_task" method="post" action="{{ route('store') }}" autocomplete="off" class="bg-dark ">
        @csrf
        <div class="form-group text-center" style="padding-top:1.6em; font-size:1.125em;">
            <label for="title">Title of your task:</label>
            <input type="text" class="form-control" id="title" name="title">
        </div>

        <hr class="bg-warning">

        <div class="form-group text-center" style=font-size:1.125em;">
            <label for="description">Description of your task:</label>
            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
        </div>

        <hr class="bg-warning">

        <div class="form-group">
           For which day(s)?
        </div>

        <div class="form-group">

            <div class="form-check">
                <input type="checkbox" class="form-check-input bg-dark border border-light" name="monday" id="monday">
                <label class="form-check-label" for="monday">Monday</label>
            </div>

            <hr class="bg-warning">

            <div class="form-check">
                <input type="checkbox" class="form-check-input bg-dark border border-light" name="tuesday" id="tuesday">
                <label class="form-check-label" for="tuesday">Tuesday</label>
            </div>

            <hr class="bg-warning">

            <div class="form-check">
                <input type="checkbox" class="form-check-input bg-dark border border-light" name="wednesday" id="wednesday">
                <label class="form-check-label" for="wednesday">Wednesday</label>
            </div>

            <hr class="bg-warning">

            <div class="form-check">
                <input type="checkbox" class="form-check-input bg-dark border border-light" name="thursday" id="thursday">
                <label class="form-check-label" for="thursday">Thursday</label>
            </div>

            <hr class="bg-warning">

            <div class="form-check">
                <input type="checkbox" class="form-check-input bg-dark border border-light" name="friday" id="friday">
                <label class="form-check-label" for="friday">Friday</label>
            </div>

            <hr class="bg-warning">

            <div class="form-check">
                <input type="checkbox" class="form-check-input bg-dark border border-light" name="saturday" id="saturday">
                <label class="form-check-label" for="saturday">Saturday</label>
            </div>

            <hr class="bg-warning">

            <div class="form-check">
                <input type="checkbox" class="form-check-input bg-dark border border-light" name="sunday" id="sunday">
                <label class="form-check-label" for="sunday">Sunday</label>
            </div>

            <hr class="bg-warning">

            <div class="form-group text-center">
                When is the task due?
                <input class="form-control text-center" id="datepicker_create" name="datepicker_create">
            </div>

            <hr class="bg-warning">

            <div class="form-group">
                <label for="timepicker">What time?</label>
                <select class="form-select text-center" style="width:20%;min-width:100px;" id="timepicker" name="timepicker">
                    @for($i=0; $i<24; $i++)
                        <option value="<?php echo $i.":00"; ?>"><?php echo $i . ":00"; ?></option>
                        <option value="<?php echo $i.":30"; ?>"><?php echo $i . ":30"; ?></option>
                    @endfor
                </select>
            </div>

            <hr class="bg-warning">

            <div class="form-group">
                  Repeat your task on the selected day(s)?
                <br>

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="repeating" id="repeating">
                    <label class="form-check-label" for="repeating"> <button style="font-size:18px;">&#10227;</button> Repeat </label>
                </div>
            </div>
        </div>

        <hr class="bg-warning">

        <div class=" text-center align-self-end form-group">
            <button type="submit" class="btn btn-primary">Save</button>
            <button type="button" class="btn btn-outline-secondary" onclick="closeCreateForm()">Close</button>
        </div>
    </form>
</div>

<link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

<script src="https://code.jquery.com/jquery-3.6.0.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

<script>
    $(function () {
        $("#datepicker_create").datepicker({
            minDate: 0,
            dateFormat: 'dd/mm/yy'
        });
    });
</script>
