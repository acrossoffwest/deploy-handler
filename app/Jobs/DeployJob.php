<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeployJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $data = $this->data;
        
            if (empty($data)) {
                return 'Ok';
            }
            logs()->info(print_r($data, true));
            $repo = $data['repository'];
            $branch = str_replace('refs/heads/', '', $data['ref'] ?? 'master');
            $projectName = $repo['name'];
            if (!in_array($branch, ['develop', 'master'])) {
                logs()->info('Branch won\'t deployed cause you need push branches: master, develop');
                return;
            }
            $runComposerExtraCommands = preg_match('/domda_.*_service/', $projectName) ? ' && composer install' : '';
            $command = 'ssh ubuntu@dev.domda.su -t "'.
            'cd ~/projects/'.$projectName.
            ' && git pull origin '.$branch.' '.
            $runComposerExtraCommands.
            ' && /home/ubuntu/.composer/vendor/bin/ldc restart'.
            '"';
            $result = exec($command);
            logs()->info($command);
            logs()->info('Deploy done.');
            logs()->info(print_r($result, true));
            
            $this->notify($this->getMessage($projectName, $branch, $data['pusher']['name']));
        } catch (\Exception $ex) {
            $this->notify('Something went wrong. Please check your deploy logs.');
            throw $ex;
        }
    }

    private function getMessage($projectName, $branch, $pusherName)
    {
        return <<<EOT
#deploy

Repository: Onza-Me/$projectName
Branch: $branch
Pusher: $pusherName
EOT;
    }

    private function notify($message)
    {
        dispatch(new NotifyToTelegram(
            $message,
            [
            -1001384022523
            ]
        ));
    }
}
