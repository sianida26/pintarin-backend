<?php

namespace App\Console\Commands;

use App\Models\User;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CleanTestUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean generated models from test cases';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = User::where('email', 'LIKE', '%@example%');
        $count = $users->count();
        $users->forceDelete();
        $this->info($count . ' ' . Str::of('user')->plural($count) . ' have been deleted');
    }
}
