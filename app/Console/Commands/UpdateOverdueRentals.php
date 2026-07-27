<?php

namespace App\Console\Commands;

use App\Services\RentalService;
use Illuminate\Console\Command;

class UpdateOverdueRentals extends Command
{
    protected $signature   = 'rentals:update-overdue';
    protected $description = 'Tandai rental yang melewati jatuh tempo';

    public function __construct(private RentalService $rentalService)
    {
        parent::__construct();
    }

    public function handle()
    {
        $count = $this->rentalService->updateOverdueRentals();
        $this->info("$count rental ditandai overdue.");
    }
}