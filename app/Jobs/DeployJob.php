<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use App\Helpers\ArrayMapper;

class DeployJob extends BaseSshCommandJob implements ShouldQueue
{
    public ArrayMapper $data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data = [])
    {
        $this->data = array_mapper($data);
    }

    protected function get($key, $default = null)
    {
        return $this->data->get($key, $default);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->data->isEmpty()) {
            return;
        }
        $branch = str_replace('refs/heads/', '', $this->get('ref') ?? 'master');

        if (!in_array($branch, ['develop', 'master'])) {
            logs()->info('Branch won\'t deployed cause you need push branches: master, develop');
            return;
        }

        $repo = array_mapper($this->get('repository') ?? []);
        $projectName = $repo->get('name');
        $repoFullname = $repo->get('full_name');
        $sshCredsName = get_ssh_creds_name($repoFullname);
        if (empty($repoFullname) || empty($sshCredsName)) {
            return;
        }

        $pusherName = $this->get('pusher.name', 'Pusher is undefined');
        $configs = array_mapper($this->getDeployConfigs($projectName));
        try {
            $this->runCommands($sshCredsName, array_merge([
                'cd '.$configs->get('project.path', '~/projects/'.$projectName),
                'git reset --hard HEAD',
                'git pull origin '.$branch
            ], $this->getExtraCommands($projectName)));

            $this->notify($this->getMessage($projectName, $branch, $pusherName)."\n\n".$this->getCommitsText($this->get('commits')));
        } catch (\Exception $e) {
            $this->notify(<<<EOT
            Something went wrong. Please check your deploy logs.


            #deploy #failed

            Repository: `{$repo->get('full_name')}`
            Branch: `$branch`
            Pusher: `$pusherName`
EOT);
            logs()->info($e->getMessage());
        }
    }

    protected function getExtraCommands(string $projectName): array
    {
        $extraCommandsFilepath = storage_path('deploy/commands/'.$projectName.'.php');
        return $extraCommands = file_exists($extraCommandsFilepath) ? require($extraCommandsFilepath) : [];
    }

    protected function getDeployConfigs(string $projectName): array
    {
        $extraCommandsFilepath = storage_path('deploy/configs/'.$projectName.'.php');
        return $extraCommands = file_exists($extraCommandsFilepath) ? require($extraCommandsFilepath) : [];
    }

    protected function getCommitsText(array $commits = [])
    {
        if (empty($commits)) {
            return 'No commits';
        }

        $text = 'Commits:'."\n";
        foreach ($commits as $index => $commit) {
            $commit = array_mapper($commit);
            $text .= '```'."\n".$commit->get('message').'```'."\n";
            $text .= '© `'.$commit->get('author.name')."` in [commit](".$commit->get('url').")\n\n";
        }

        return $text;
    }

    protected function getMessage($projectName, $branch, $pusherName)
    {
        return <<<EOT
#deploy

Repository: `acrossoffwest/$projectName`
Branch: `$branch`
Pusher: `$pusherName`
EOT;
    }

    protected function notify($message)
    {
        dispatch(new NotifyToTelegram(
            $message,
            [
                -1001383658966
            ]
        ));
    }
}
