<?php

namespace App\Console\Commands;

use App\Http\Controllers\TaskCompletionController;
use Illuminate\Console\Command;

class InsertRepeatingTaskCompletions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:insert-repeating-tasks-completions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Every week, repeating tasks need to be renewed for the next week, this command accomplishes exactly that';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        TaskCompletionController::prepareRepeatingTasksForNextWeek();

        return Command::SUCCESS;
    }
}
