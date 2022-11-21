<style>
    /* The popup form - hidden by default */
    .form-popup {
        width: 50%;
        height: 44%;
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
    .form-check{
        padding-right:10px;
    }
    .parag{
        padding-left:10px;
        padding-top:10px;
    }
</style>

<div class="form-popup bg-dark text-white" id="createTaskForm">
    <form method="post" action="{{ route('store') }}">
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
                <p>Repeat your task on the selected day(s)?</p>
                <br>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="repeating" id="repeating">
                    <label class="form-check-label" for="repeating">Repeat</label>
                </div>
            </div>
        </div>

        <div class="col-md-12 text-center align-self-end form-group">
            <button type="submit" class="btn btn-primary">Save</button>
            <button type="button" class="btn btn-outline-secondary" onclick="closeCreateForm()">Close</button>
        </div>
    </form>
</div>
