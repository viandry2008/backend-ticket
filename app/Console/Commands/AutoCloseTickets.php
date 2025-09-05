<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoCloseTickets extends Command
{
    protected $signature = 'tickets:auto-close';
    protected $description = 'Auto close resolved tickets after X days with no reply';

    public function handle()
    {
        $days = config('tickets.auto_close_days', 3); // default 3 hari
        $cutoff = Carbon::now()->subDays($days);

        $tickets = Ticket::where('status', 'Resolved')
            ->where('resolved_at', '<=', $cutoff)
            ->get();

        foreach ($tickets as $ticket) {
            $ticket->status = 'Closed';
            $ticket->save();

            $this->info("Ticket #{$ticket->id} auto-closed.");
        }
    }
}
