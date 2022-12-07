<?php

namespace Tests\Http\Controllers;

use App\Http\Controllers\TaskController;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    private User $mockUser;
    private TaskController $controller;

    function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        //make a mock user
        $this->mockUser = new User([
            'id' => 1,
            'name' => 'Test User'
        ]);

        // instantiate the TaskController
        $this->controller = new TaskController();
    }

    /**
     * The index of TaskController can work together with a datepicker.
     * By default, the index shows us the current week and the tasks for the current week.
     * When another date is selected on the datepicker, the index takes a request and shows us
     * the week of the selected date.
     *
     * @return void
     */
    public function test_index_with_request_date_string_from_date_picker(): void
    {
        $this->actingAs($this->mockUser);

        $carbonDate = Carbon::now()->isoFormat('DD/MM/YYYY');
        $response = $this->call('GET', '/list', ['selected_week' => $carbonDate]);

        $response->assertStatus(200);
    }

    /**
     * The index of TaskController can work together with a datepicker, but it doesn't have to.
     * The index will render the current week, if no datepicker date is passed to it.
     *
     * @return void
     */
    public function test_index_without_request_date_string_from_date_picker(): void
    {
        $this->actingAs($this->mockUser);

        $response = $this->call('GET', '/list', []);

        $this->assertAuthenticated();
        $response->assertStatus(200);
    }

    /**
     * getTasksForDayOfWeek Returns an array with the correct parameters passed
     *
     * @return void
     */
    public function test_getTasksForDayOfWeek_with_correct_parameter_data_returns_array(): void
    {
        $this->actingAs($this->mockUser);

        $dayOfWeekTestData = "monday";
        $dateOfDayTestData = "22/12/2022";

        $response = $this->controller->getTasksForDayOfWeek($dayOfWeekTestData, $dateOfDayTestData);

        $this->assertIsArray($response);
    }

    /**
     * getTasksForDayOfWeek Returns an array with empty parameters passed
     *
     * @return void
     */
    public function test_getTasksForDayOfWeek_with_no_parameter_data_returns_array(): void
    {
        $this->actingAs($this->mockUser);

        $dayOfWeekTestData = "";
        $dateOfDayTestData = "";

        $response = $this->controller->getTasksForDayOfWeek($dayOfWeekTestData, $dateOfDayTestData);

        $this->assertIsArray($response);
    }

    /**
     * We can always expect an array output from getTaskById
     *
     * @return void
     */
    public function test_getTaskById_with_given_id_returns_array(): void
    {
        $this->actingAs($this->mockUser);

        $testId = 51;

        $response = $this->controller->getTaskById($testId);

        $this->assertIsArray($response);
    }

    public function test_edit_with_valid_request()
    {
        // Create a new task
        $task = new Task();
        $task->description = "Test Task";
        $task->repeating = "on";
        $task->monday = "off";
        $task->tuesday = "off";
        $task->wednesday = "on";
        $task->thursday = "off";
        $task->friday = "on";
        $task->saturday = "off";
        $task->sunday = "on";
        $task->user_fid = 1;
        $task->date_due = "05/12/2022";
        $task->time_due = "12:00";
        $task->save();

        // Create data to update task
        $taskUpdateData['description'] = "This is a test task";
        $taskUpdateData['repeating'] = "on";
        $taskUpdateData['monday'] = "on";
        $taskUpdateData['tuesday'] = "off";
        $taskUpdateData['wednesday'] = "on";
        $taskUpdateData['thursday'] = "off";
        $taskUpdateData['friday'] = "on";
        $taskUpdateData['saturday'] = "off";
        $taskUpdateData['sunday'] = "on";
        $taskUpdateData['user_fid'] = 2;
        $taskUpdateData['timepicker_edit'] = "12:00";
        $taskUpdateData['datepicker_edit'] = "05/12/2022";


        // Post to edit method
        $this->call('POST', '/list/edit', [
            'id' => $task->id,
            'description' => $taskUpdateData['description'],
            'repeating' => $taskUpdateData['repeating'],
            'monday' => $taskUpdateData['monday'],
            'tuesday' => $taskUpdateData['tuesday'],
            'wednesday' => $taskUpdateData['wednesday'],
            'thursday' => $taskUpdateData['thursday'],
            'friday' => $taskUpdateData['friday'],
            'saturday' => $taskUpdateData['saturday'],
            'sunday' => $taskUpdateData['sunday'],
            'user_fid' => $taskUpdateData['user_fid'],
            'timepicker_edit' => $taskUpdateData['timepicker_edit'],
            'datepicker_edit' => $taskUpdateData['datepicker_edit'],
        ]);

        // Retrieve the updated task from the database
        $updatedTask = Task::find($task->id);

        // Assert that the updated task has the expected attributes
        $this->assertEquals($taskUpdateData['description'], $updatedTask->description);
        $this->assertEquals($taskUpdateData['repeating'], $updatedTask->repeating);
        $this->assertEquals($taskUpdateData['monday'], $updatedTask->monday);
        $this->assertEquals($taskUpdateData['tuesday'], $updatedTask->tuesday);
        $this->assertEquals($taskUpdateData['wednesday'], $updatedTask->wednesday);
        $this->assertEquals($taskUpdateData['thursday'], $updatedTask->thursday);
        $this->assertEquals($taskUpdateData['friday'], $updatedTask->friday);
        $this->assertEquals($taskUpdateData['saturday'], $updatedTask->saturday);
        $this->assertEquals($taskUpdateData['sunday'], $updatedTask->sunday);
        $this->assertEquals($taskUpdateData['user_fid'], $updatedTask->user_fid);
        $this->assertEquals($taskUpdateData['timepicker_edit'], $updatedTask->time_due);
        $this->assertEquals($taskUpdateData['datepicker_edit'], $updatedTask->date_due);

        // Delete the task from the database
        $task->delete();
    }
}
