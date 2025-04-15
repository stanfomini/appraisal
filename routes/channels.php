<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// routes/channels.php
Broadcast::channel('kanban', function ($user) {
    return true; // Allow all authenticated users for testing
});

Broadcast::channel('leads.{id}', function ($user, $id) {
    return true; // Adjust authorization if needed
});
