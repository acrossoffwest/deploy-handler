<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BaseSshCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected function runCommands(string $sshCredName, array $commands)
    {
        $sshUserAndHost = config('ssh.'.$sshCredName);
        if (empty($sshUserAndHost)) {
            logs()->error('SSH credentials was empty for "'.$sshCredName.'"');
            return;
        }

        $cmd = 'ssh -o "StrictHostKeyChecking no" '.$sshUserAndHost.' -t "{commands}"';
        $cmd = str_replace('{commands}', implode(' && ', $commands), $cmd);
        logs()->info('Running command: '.$cmd);

        $result = exec($cmd);
        $result = print_r($result, true);

        logs()->info('Command execution result: '.$result);
    }
}
