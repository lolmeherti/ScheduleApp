<style>
    /* The popup form - hidden by default */
    .form-popup {
        width: 50%;
        height: 58%;
        position: absolute;
        top: 10%;
        left: 15%;
        display: none;
        z-index: 10;
        opacity: 0.9;
    }
    .form-group {
        padding: 10px;
    }
    .form-check {
        padding-right: 10px;
    }
    .parag {
        padding-left: 10px;
        padding-top: 10px;
    }
</style>

<div class="form-popup bg-dark text-white" id="createTaskForm">
    <form method="post" action="{{ route('store') }}" autocomplete="off">
        @csrf
        <div class="form-group text-center">
            <label for="description">Description of your task:</label>
            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
        </div>

        <hr>

        <p class="parag">For which day(s)?</p>
        <div class="form-group">

            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="monday" id="monday">
                <label class="form-check-label" for="monday">Monday</label>
            </div>

            <hr>

            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="tuesday" id="tuesday">
                <label class="form-check-label" for="tuesday">Tuesday</label>
            </div>

            <hr>

            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="wednesday" id="wednesday">
                <label class="form-check-label" for="wednesday">Wednesday</label>
            </div>

            <hr>

            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="thursday" id="thursday">
                <label class="form-check-label" for="thursday">Thursday</label>
            </div>

            <hr>

            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="friday" id="friday">
                <label class="form-check-label" for="friday">Friday</label>
            </div>

            <hr>

            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="saturday" id="saturday">
                <label class="form-check-label" for="saturday">Saturday</label>
            </div>

            <hr>

            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="sunday" id="sunday">
                <label class="form-check-label" for="sunday">Sunday</label>
            </div>

            <hr>

            <div class="form-group">
                <label for="timepicker">What time?</label>
                <select class="form-select text-center" style="width:20%" id="timepicker" name="timepicker">
                    @for($i=0; $i<24; $i++)
                        <option value="<?php echo $i.":00"; ?>"><?php echo $i.":00"; ?></option>
                        <option value="<?php echo $i.":30"; ?>"><?php echo $i.":30"; ?></option>
                    @endfor
                </select>
            </div>

            <hr>

            <div class="form-group text-center">
                <label for="datepicker_create">When is the task due?</label>
                <input class="form-control text-center" id="datepicker_create" name="datepicker_create">
            </div>

            <hr>

            <div class="form-group">
                <p>Repeat your task on the selected day(s)?</p>
                <br>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="repeating" id="repeating">
                    <label class="form-check-label" for="repeating">Repeat</label>
                </div>
            </div>
        </div>

        <hr>

        <div class="col-md-12 text-center align-self-end form-group">
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
