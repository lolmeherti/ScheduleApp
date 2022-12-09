<style>
    /* The popup form - hidden by default */
    .form-popup {
        width: 50%;
        min-width:300px;
        height:75%;
        position: fixed;
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

</style>

<div class="form-popup bg-dark border border-warning text-white" id="editTaskForm">
    <form method="post" action="{{ route('edit') }}" autocomplete="off" class="bg-dark border border-warning">
        @csrf
        <input type="hidden" value="" name="id" id="id">

        <div class="form-group text-center" style="padding-top:1.6em; font-size:1.125em;">
            <label for="title">Title of your task:</label>
            <input type="text" class="form-control" id="title" name="title">
        </div>

        <hr class="bg-warning">

        <div class="form-group text-center" style="padding-top:1em; font-size:1.2em;">
            <label for="description">Description of your task:</label>
            <textarea class="form-control" id="description" name="description"
                      rows="3"></textarea>
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
                <input class="form-control text-center" id="datepicker_edit" name="datepicker_edit">
            </div>

            <hr class="bg-warning">

            <div class="form-group">
                <label for="timepicker_edit">What time?</label>
                <select class="form-select text-center" style="width:20%; min-width:100px;" id="timepicker_edit" name="timepicker_edit">
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
                    <label class="form-check-label" for="repeating"> <button style="font-size:1.2em;">&#10227;</button> Repeat </label>
                </div>
            </div>
        </div>

        <hr class="bg-warning">

        <div class="col-md-12 text-center align-self-end form-group">
            <button type="submit" class="btn btn-primary">Save</button>
            <button type="button" class="btn btn-outline-secondary" onclick="closeEditForm()">Close</button>
        </div>
    </form>
</div>

<link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

<script src="https://code.jquery.com/jquery-3.6.0.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

<script>
    $(function () {
        $("#datepicker_edit").datepicker({
            minDate: 0,
            dateFormat: 'dd/mm/yy'
        });
    });

    function fillTaskEditForm(taskId) {
        $.ajax({
            type: "GET",
            url: '/list/show/' + taskId,
            success: function (response) {

                if(response.status == 404){
                    //TODO: display error message
                } else {

                    //setting the task id in a hidden input
                    response.task.id ?  $('#id').val(response.task.id) : '';

                    //load title
                    $('#title').val(response.task.title);

                    //load description
                    $('#description').val(response.task.description);

                    //days checkboxes
                    response.task.monday==='on' ?  $( '#monday' ).prop( "checked", true ) : $('#monday').prop( 'checked', false );
                    response.task.tuesday==='on' ?  $( '#tuesday' ).prop( 'checked', true ) : $('#tuesday').prop( 'checked', false );
                    response.task.wednesday==='on' ?  $( '#wednesday' ).prop( 'checked', true ) : $('#wednesday').prop( 'checked', false );
                    response.task.thursday==='on' ?  $( '#thursday' ).prop( 'checked', true ) : $('#thursday').prop( 'checked', false );
                    response.task.friday==='on' ?  $( '#friday' ).prop( 'checked', true ) : $('#friday').prop( 'checked', false );
                    response.task.saturday==='on' ?  $( '#saturday' ).prop( 'checked', true ) : $('#saturday').prop( 'checked', false );
                    response.task.sunday==='on' ?  $( '#sunday' ).prop( 'checked', true ) : $('#sunday').prop( 'checked', false );

                    //repeating checkbox
                    response.task.repeating==='on' ?  $( '#repeating' ).prop( 'checked', true ) : $('#repeating').prop( 'checked', false );

                    //date due
                    response.task.date_due ?  $('#datepicker_edit').val(response.task.date_due) : '';

                    //time due
                    response.task.time_due ?  $('#timepicker_edit').val(response.task.time_due) : $('#timepicker_edit').val('0:00');
                }
            }
        });
    }
</script>
