<?php

use App\Models\Client;
use App\Models\Master;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
 * Public channel for the admin map view — broadcasts every master location update.
 * The service covers Ashgabat only, so a single channel carries all masters.
 * Anyone (including unauthenticated guest in dev) can listen.
 * In production, switch to private channel + admin auth gate.
 */
Broadcast::channel('masters-map', function () {
    return true;
});

/*
 * Private channel carrying OTP codes parked for manual delivery. Codes are
 * secrets — only staff who can open the section may subscribe.
 */
Broadcast::channel('admin.pending-otps', function ($user) {
    return $user instanceof User && ! $user->isOperator();
});

/*
 * Private channel for a specific client — used by the mobile client app to receive:
 * master.assigned and order.status.changed events scoped to their orders.
 * Auth: Sanctum token issued to the Client model.
 */
Broadcast::channel('client.{clientId}', function ($user, $clientId) {
    return $user instanceof Client && (int) $user->id === (int) $clientId;
});

/*
 * Private channel for a specific master — used by the mobile master app to receive:
 * master.assigned (new job) and order.status.changed events.
 * Auth: Sanctum token issued to the Master model.
 */
Broadcast::channel('master.{masterId}', function ($user, $masterId) {
    return $user instanceof Master && (int) $user->id === (int) $masterId;
});
