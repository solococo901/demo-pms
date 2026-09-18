<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Pms\DashboardController;
use App\Http\Controllers\Pms\RoomTypeController;
use App\Http\Controllers\Pms\RoomController;
use App\Http\Controllers\Pms\RoomMoveController;
use App\Http\Controllers\Pms\GuestController;
use App\Http\Controllers\Pms\ReservationController;
use App\Http\Controllers\Pms\RatePlanController;
use App\Http\Controllers\Pms\RateCalendarController;
use App\Http\Controllers\Pms\InventoryController;
use App\Http\Controllers\Pms\ChannexController;
use App\Http\Controllers\Pms\FrontDeskController;
use App\Http\Controllers\Pms\HousekeepingController;
use App\Http\Controllers\Pms\PaymentController;
use App\Http\Controllers\Pms\FolioController;
use App\Http\Controllers\Pms\NoShowController;


/*
|--------------------------------------------------------------------------
| Public Website
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});


/*
|--------------------------------------------------------------------------
| PMS
|--------------------------------------------------------------------------
*/

Route::prefix('pms')
    ->name('pms.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/',
            [DashboardController::class, 'index']
        )->name('dashboard');



        /*
        |--------------------------------------------------------------------------
        | Front Desk
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/front-desk',
            [
                FrontDeskController::class,
                'index',
            ]
        )->name(
                'front-desk.index'
            );


        Route::post(
            '/front-desk/{reservation}/check-in',
            [
                FrontDeskController::class,
                'checkIn',
            ]
        )->name(
                'front-desk.check-in'
            );


        Route::post(
            '/front-desk/{reservation}/check-out',
            [
                FrontDeskController::class,
                'checkOut',
            ]
        )->name(
                'front-desk.check-out'
            );


        /*
        |--------------------------------------------------------------------------
        | Room Types
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'room-types',
            RoomTypeController::class
        )->except('show');


        /*
        |--------------------------------------------------------------------------
        | Physical Rooms
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'rooms',
            RoomController::class
        )->except('show');


        /*
        |--------------------------------------------------------------------------
        | Guests
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'guests',
            GuestController::class
        )->except('show');


        /*
        |--------------------------------------------------------------------------
        | Reservations - Special Actions
        |--------------------------------------------------------------------------
        |
        | Các route cụ thể nên đặt trước Route::resource().
        |
        */

        Route::post(
            '/reservations/{reservation}/assign-room',
            [
                ReservationController::class,
                'assignRoom',
            ]
        )->name(
                'reservations.assign-room'
            );


        Route::delete(
            '/reservations/{reservation}/unassign-room',
            [
                ReservationController::class,
                'unassignRoom',
            ]
        )->name(
                'reservations.unassign-room'
            );


        /*
        |--------------------------------------------------------------------------
        | Housekeeping
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/housekeeping',
            [
                HousekeepingController::class,
                'index',
            ]
        )->name(
                'housekeeping.index'
            );


        Route::patch(
            '/housekeeping/rooms/{room}',
            [
                HousekeepingController::class,
                'updateStatus',
            ]
        )->name(
                'housekeeping.update-status'
            );


        /*
        |--------------------------------------------------------------------------
        | No-show
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/reservations/{reservation}/no-show',
            [
                NoShowController::class,
                'store',
            ]
        )->name(
            'reservations.no-show'
        );

        /*
        |--------------------------------------------------------------------------
        | Reservations
        |--------------------------------------------------------------------------
        |
        | Bao gồm:
        |
        | GET       /pms/reservations
        | GET       /pms/reservations/create
        | POST      /pms/reservations
        | GET       /pms/reservations/{reservation}
        | GET       /pms/reservations/{reservation}/edit
        | PUT       /pms/reservations/{reservation}
        | PATCH     /pms/reservations/{reservation}
        | DELETE    /pms/reservations/{reservation}
        |
        */

        Route::resource(
            'reservations',
            ReservationController::class
        );



        /*
        |--------------------------------------------------------------------------
        | Payments
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/reservations/{reservation}/payments',
            [
                PaymentController::class,
                'store',
            ]
        )->name(
                'payments.store'
            );


        Route::patch(
            '/payments/{payment}/void',
            [
                PaymentController::class,
                'void',
            ]
        )->name(
                'payments.void'
            );


        /*
        |--------------------------------------------------------------------------
        | Refund Payment
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/payments/{payment}/refund',
            [
                PaymentController::class,
                'refund',
            ]
        )->name(
                'payments.refund'
            );


        /*
        |--------------------------------------------------------------------------
        | Void Refund
        |--------------------------------------------------------------------------
        */

        Route::patch(
            '/refunds/{refund}/void',
            [
                PaymentController::class,
                'voidRefund',
            ]
        )->name(
                'refunds.void'
            );



        /*
        |--------------------------------------------------------------------------
        | Room Move
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/reservations/{reservation}/room-move',
            [
                RoomMoveController::class,
                'create',
            ]
        )->name(
                'room-moves.create'
            );


        Route::post(
            '/reservations/{reservation}/room-move',
            [
                RoomMoveController::class,
                'store',
            ]
        )->name(
                'room-moves.store'
            );

        /*
        |--------------------------------------------------------------------------
        | Rate Plans
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'rate-plans',
            RatePlanController::class
        )->except('show');


        /*
        |--------------------------------------------------------------------------
        | Rate Calendar
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/rates',
            [
                RateCalendarController::class,
                'index',
            ]
        )->name(
                'rate-calendar.index'
            );


        Route::post(
            '/rates',
            [
                RateCalendarController::class,
                'update',
            ]
        )->name(
                'rate-calendar.update'
            );


        /*
        |--------------------------------------------------------------------------
        | Inventory
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/inventory',
            [
                InventoryController::class,
                'index',
            ]
        )->name(
                'inventory.index'
            );


        Route::post(
            '/inventory',
            [
                InventoryController::class,
                'update',
            ]
        )->name(
                'inventory.update'
            );


        /*
        |--------------------------------------------------------------------------
        | Channex
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/channex',
            [
                ChannexController::class,
                'index',
            ]
        )->name(
                'channex.index'
            );


        /*
        |--------------------------------------------------------------------------
        | Channex - Property Mapping
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/channex/map-property',
            [
                ChannexController::class,
                'mapProperty',
            ]
        )->name(
                'channex.map-property'
            );


        /*
        |--------------------------------------------------------------------------
        | Channex - Room Type Mapping
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/channex/map-room-types',
            [
                ChannexController::class,
                'mapRoomTypes',
            ]
        )->name(
                'channex.map-room-types'
            );


        /*
        |--------------------------------------------------------------------------
        | Folio Items
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/reservations/{reservation}/folio-items',
            [
                FolioController::class,
                'store',
            ]
        )->name(
                'folio-items.store'
            );


        Route::patch(
            '/folio-items/{folioItem}/void',
            [
                FolioController::class,
                'void',
            ]
        )->name(
                'folio-items.void'
            );


        /*
        |--------------------------------------------------------------------------
        | Channex - Rate Plan Mapping
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/channex/map-rate-plans',
            [
                ChannexController::class,
                'mapRatePlans',
            ]
        )->name(
                'channex.map-rate-plans'
            );


        /*
        |--------------------------------------------------------------------------
        | Channex - Disconnect
        |--------------------------------------------------------------------------
        */

        Route::delete(
            '/channex/disconnect',
            [
                ChannexController::class,
                'disconnect',
            ]
        )->name(
                'channex.disconnect'
            );
    });