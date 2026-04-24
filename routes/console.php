<?php

use Illuminate\Support\Facades\Schedule;

// Planification de votre commande
Schedule::command('payment:check-status')->everyTwoMinutes();