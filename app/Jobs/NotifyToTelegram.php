<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class NotifyToTelegram implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $message = 'Default mesage';
    protected array $receivers = [];
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $message, array $receivers)
    {
        $this->message = $message;
        $this->receivers = $receivers;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $response = Http::post('https://ts3-telegram.aow.space/notifications', [
            'message' => $this->message,
            'receivers' => $this->receivers
        ]);

        $this->log($response->json());
    }

    private function log($out)
    {
        logs()->info(print_r($out, true));
    }
}
