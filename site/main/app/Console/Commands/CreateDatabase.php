<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PDO;

class CreateDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-database {dbuser} {dbname} {dbpassword}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to add new database with user defined';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dbuser = $this->argument('dbuser');
        $dbname = $this->argument('dbname');
        $dbpassword = $this->argument('dbpassword');

        $dsn = 'mysql:host=' . env('DB_HOST');
        $username = env('DB_USERNAME');
        $password = env('DB_PASSWORD');

        Log::info("Creating database", [
            'dbuser' => $dbuser,
            'dbname' => $dbname,
            'dbpassword' => $dbpassword
        ]);

        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        try {
            
            // Create the database
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Create the user and grant privileges if not exists
            $pdo->exec("CREATE USER IF NOT EXISTS '$dbuser'@'localhost' IDENTIFIED BY '$dbpassword'");
            $pdo->exec("GRANT ALL PRIVILEGES ON `$dbname`.* TO '$dbuser'@'localhost'");
            $pdo->exec("FLUSH PRIVILEGES");

            Log::info("Success creating database", [
                'dbuser' => $dbuser,
                'dbname' => $dbname,
                'dbpassword' => $dbpassword
            ]);

            return true;
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Log::error("Failed to create database or user: " . $e->getMessage());
            Log::error("Exception Trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string, string>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'dbuser' => 'Database user cannot be emmpty',
            'dbname' => 'Database name cannot be empty',
            'dbpassword' => 'Database password cannot be emmpty',
        ];
    }
}
