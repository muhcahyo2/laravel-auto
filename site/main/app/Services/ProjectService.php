<?php

namespace App\Services;

use App\Models\Project;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ProjectService
{
    public function __construct(protected Project $project)
    {
    }

    public function generateDB()
    {
        $ctx = [
            'dbuser' => $this->project->dbuser,
            'dbname' => $this->project->dbname,
            'dbpassword' => $this->project->dbpassword,
        ];
        Log::info("Generate new db", $ctx);
        try {
            $result = Artisan::call('app:create-database', $ctx);
            return $result;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'failed generate new database',
            ], 500));
        }
    }

    public function generateConfigNginx()
    {
        if($this->project->mode == Project::MODE_LARAVEL){
            $file = getcwd() . '/../config/template/nginx-config-template-php.txt';
        }

        $domain = env('APP_DOMAIN');
        $subdomain = $this->project->subdomain;

        Log::info('generate config', [
            'template' => $file,
            'domain' => $domain,
            'subdomain' => $subdomain
        ]);

        // check apakah file sudah ada jika sudah ada langsung return true
        $name = $subdomain . '.' . $domain . '.conf';

        $files = self::getFiles(getcwd().'/../storage/webserver/');
        $files = array_column($files, 'name');

        if(in_array($name, $files)) {
            Log::info('config nginx already exsist', [
                'filename' => $name
            ]);
            return true;
        }


        try {
            $file = file_get_contents($file);
            $file = str_replace(['_domain','_subdomain'],[$domain, $subdomain], $file);
            $result = file_put_contents(getcwd().'/../storage/webserver/'. $name, $file);
            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'failed to generate config web server',
            ], 500));
        }

    }

    public function pullProject()
    {
        $folders = self::getFiles();
        $folders_name = array_column($folders, 'name');

        $folder_name = $this->project->subdomain;
        $folder_out = "/var/www/html/$folder_name";
        $repo = $this->project->repository_url;

        Log::info('Start pull/clone Project', [
            "dir" => $folder_out,
            'repo' => $repo
        ]);

        try {
            // Define the pipes that will be used
            $descriptors = [
                0 => ['pipe', 'r'],  // stdin
                1 => ['pipe', 'w'],  // stdout
                2 => ['pipe', 'w']   // stderr
            ];
            if (!in_array($folder_name, $folders_name)) {
                Log::info('Folder not exsist do cloning Project');

                // Command to execute
                $cmd = "git clone $repo $folder_out";
            } else {
                Log::info('Folder is Already exsist pulling Project');
                // Command to execute
                shell_exec("mkdir $folder_out");
                $cmd = "git -C $folder_out pull";
            }

            Log::info("Command running $cmd");
            $process = proc_open($cmd, $descriptors, $pipes);
            if (is_resource($process)) {
                // Capture the output
                $output = stream_get_contents($pipes[1]);
                fclose($pipes[1]);

                // Capture the errors
                $errors = stream_get_contents($pipes[2]);
                fclose($pipes[2]);

                if(!empty($errors)){
                    throw new Exception("Failed to $cmd, errors: $errors");
                }

                // Close the process
                $return_value = proc_close($process);
                Log::info('results', [
                    'errors' => $errors,
                    'outputs' => $output,
                    'results' => $return_value
                ]);
                return true;
            } else {
                throw new \Exception('Something error');
            }
        } catch (\Exception $e) {
            Log::error("filed pull/clone project",[
                'message' => $e->getMessage(),
                'tracing' => $e->getTraceAsString()
            ]);
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'failed to pull/clone project',
            ], 500));
        }
    }

    public function createEnvFromExample()
    {
        $subdomain = $this->project->subdomain;
        $folder_dir = "/var/www/html/$subdomain";

        $files = self::getFiles($folder_dir);
        $files_name = array_column($files, 'name');

        if(empty($files_name) && !in_array('.env.example', $files_name)){
            Log::info('.env.example is not found', [
                'dir' => $folder_dir,
                'project' => $this->project
            ]);
            return;
        }

        Log::info('copy .env.example to .env', [
            'dir' => $folder_dir,
            'project' => $this->project
        ]);

        shell_exec("cp $folder_dir/.env.example $folder_dir/.env");
        return true;
    }

    public function runCommand()
    {
        $project = $this->project;
        $dir = $this->project->path;
        $commands = $project->commands->sortBy('order');

        foreach($commands as $command)
        {
            if(str_contains($command->commands, 'composer')){
                $command->commands = $command->commands . ' --ignore-platform-req=php';
            }
            try {
                Log::info('Running command', [
                    'command' => $command->commands
                ]);
                $result = shell_exec("cd $dir && $command->commands");
                Log::info('result exec command', [
                    'result' => $result
                ]);
            } catch (\Exception $e) {
                Log::error('failed run command', [
                    'commands' => $command->commands
                ]);
                throw new HttpResponseException(response()->json([
                    'success' => false,
                    'message' => 'failed to pull/clone project',
                ], 500));
            }
            sleep(1);
        }

        return true;
    }

    public static function getFiles($base_directory = '/var/www/html'): array
    {
        $files = [];

        // Jalankan perintah shell `ls -l` dan tampung outputnya
        $output = shell_exec("ls -la " . escapeshellarg($base_directory));

        // Pisahkan output menjadi baris-baris individu
        $lines = explode("\n", trim($output));

        // Proses setiap baris
        foreach ($lines as $line) {
            // Lewati baris header (baris pertama dari output `ls -l`)
            if (strpos($line, 'total') === 0) {
                continue;
            }

            // Pisahkan baris menjadi kolom
            $columns = preg_split('/\s+/', $line, 9);

            // Tambahkan informasi file atau direktori ke dalam array
            if (count($columns) === 9) {
                if($columns[8] == '.' || $columns[8] == '..') {
                    continue;
                }
                $files[] = [
                    'permissions' => $columns[0],
                    'links' => $columns[1],
                    'owner' => $columns[2],
                    'group' => $columns[3],
                    'size' => $columns[4],
                    'month' => $columns[5],
                    'day' => $columns[6],
                    'time' => $columns[7],
                    'name' => $columns[8],
                ];
            }
        }
        return $files;
    }

    public static function parseEnvFile(string $dir, string $filename)
    {
        $env = [];
        // check terlebih dulu apakah file ada?
        $files = self::getFiles($dir);
        $files = array_column($files, 'name');

        if(!in_array($filename, $files)) {
            Log::info('file not exsist', [
                'dir' => $dir,
                'filename' => $filename
            ]);
            return $env;
        }

        $output = shell_exec("cat " . escapeshellarg("$dir/$filename"));
        $lines = explode("\n", trim($output));

        foreach ($lines as $line) {
            if(empty($line)) continue;
            $line = explode('=', $line);
            $value = $line[1];

            if($value === 'true') {
                $value = true;
            }else if($value === 'false') {
                $value = false;
            }

            $env[$line[0]] = $value;
        }
        
        return $env;
    }

    public static function writeEnv(array $envs, string $dir, string $filename)
    {
        // check terlebih dulu apakah file ada jika belum buatkan dulu?
        $files = self::getFiles($dir);
        $files = array_column($files, 'name');

        if(!in_array($filename, $files)) {
            Log::info('file not exsist creating file env', [
                'dir' => $dir,
                'filename' => $filename
            ]);
            shell_exec("touch $dir/$filename");
        } else {
            Log::info('file already exsist clear value', [
                'dir' => $dir,
                'filename' => $filename
            ]);
            shell_exec("echo '' > $dir/$filename");
        }
        foreach($envs as $key => $value) {
            $line = "$key=$value";
            shell_exec("echo $line >> $dir/$filename");
        }

        return true;
    }
    
}
