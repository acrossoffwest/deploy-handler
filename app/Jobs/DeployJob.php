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
        $data = $this->data;
        $repo = $data['repository'];
        $branch = str_replace('refs/heads/', '', $data['ref'] ?? 'master');
        $projectName = $repo['name'];
        $pusherName = $data['pusher']['name'];

        try {
            if (empty($data)) {
                return 'Ok';
            }
            if (!in_array($branch, ['develop', 'master'])) {
                logs()->info('Branch won\'t deployed cause you need push branches: master, develop');
                return;
            }
            $runComposerExtraCommands = preg_match('/domda_.*_service/', $projectName) ? ' && composer install' : '';
            $command = 'ssh ubuntu@dev.domda.su -t "'.
            'cd ~/projects/'.$projectName.
            ' && git reset --hard HEAD && git pull origin '.$branch.' '.
            $runComposerExtraCommands.
            ' && /home/ubuntu/.composer/vendor/bin/ldc restart'.
            '"';
            $result = exec($command);
            $result = print_r($result, true);
            
            if ($result != 'Done.') {
                throw new \Exception('Something went wrong.');
            }

            $this->notify($this->getMessage($projectName, $branch, $pusherName)."\n\n".$this->getCommitsText($data['commits'] ?? []));
        } catch (\Exception $ex) {
            $this->notify(<<<EOT
            Something went wrong. Please check your deploy logs.


            #deploy #failed

            Repository: `Onza-Me/$projectName`
            Branch: `$branch`
            Pusher: `$pusherName`
EOT);
        }
    }

    private function getCommitsText(array $commits = [])
    {
        if (empty($commits)) {
            return 'No commits';
        }

        $text = 'Commits:'."\n";
        foreach ($commits as $index => $commit) {
            $text .= '```'."\n".$commit['message'].'```'."\n";
            $text .= '© `'.$commit['author']['name']."` in [commit](".$commit['url'].")\n\n";
        }

        return $text;
    }

    private function getMessage($projectName, $branch, $pusherName)
    {
        return <<<EOT
#deploy

Repository: `Onza-Me/$projectName`
Branch: `$branch`
Pusher: `$pusherName`
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
