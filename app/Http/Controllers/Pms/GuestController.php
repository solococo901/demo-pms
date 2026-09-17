<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuestController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Guest List
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $property = Property::firstOrFail();

        $query = Guest::where(
            'property_id',
            $property->id
        );


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        |
        | Search theo:
        | - Guest Code
        | - First Name
        | - Last Name
        | - Phone
        | - Email
        | - ID Number
        |
        */
        if ($request->filled('search')) {

            $search = trim(
                $request->search
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'code',
                    'like',
                    '%' . $search . '%'
                )
                    ->orWhere(
                        'first_name',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'last_name',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'phone',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'email',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'id_number',
                        'like',
                        '%' . $search . '%'
                    );
            });
        }


        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */
        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Nationality Filter
        |--------------------------------------------------------------------------
        */
        if ($request->filled('nationality')) {

            $query->where(
                'nationality',
                $request->nationality
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Guests
        |--------------------------------------------------------------------------
        */
        $guests = $query
            ->orderByDesc('id')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Nationalities for filter
        |--------------------------------------------------------------------------
        */
        $nationalities = Guest::where(
            'property_id',
            $property->id
        )
            ->whereNotNull('nationality')
            ->where(
                'nationality',
                '!=',
                ''
            )
            ->distinct()
            ->orderBy('nationality')
            ->pluck('nationality');


        return view(
            'pms.guests.index',
            compact(
                'property',
                'guests',
                'nationalities'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create Guest
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $property = Property::firstOrFail();

        return view(
            'pms.guests.create',
            compact(
                'property'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store Guest
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $property = Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */
        $validated = $this->validateGuest(
            $request
        );


        /*
        |--------------------------------------------------------------------------
        | Property
        |--------------------------------------------------------------------------
        */
        $validated['property_id'] =
            $property->id;


        /*
        |--------------------------------------------------------------------------
        | Auto Generate Guest Code
        |--------------------------------------------------------------------------
        */
        $validated['code'] =
            $this->generateGuestCode(
                $property
            );


        /*
        |--------------------------------------------------------------------------
        | Create
        |--------------------------------------------------------------------------
        */
        Guest::create(
            $validated
        );


        return redirect()
            ->route('pms.guests.index')
            ->with(
                'success',
                'Guest created successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Edit Guest
    |--------------------------------------------------------------------------
    */
    public function edit(Guest $guest)
    {
        $property = Property::firstOrFail();


        /*
         * Không cho edit Guest
         * thuộc Property khác.
         */
        abort_if(
            $guest->property_id
                !==
            $property->id,
            404
        );


        return view(
            'pms.guests.edit',
            compact(
                'property',
                'guest'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Guest
    |--------------------------------------------------------------------------
    */
    public function update(
        Request $request,
        Guest $guest
    ) {
        $property = Property::firstOrFail();


        abort_if(
            $guest->property_id
                !==
            $property->id,
            404
        );


        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */
        $validated = $this->validateGuest(
            $request,
            $guest
        );


        /*
        |--------------------------------------------------------------------------
        | Guest Code không đổi
        |--------------------------------------------------------------------------
        |
        | Code là mã business cố định.
        |
        */
        $guest->update(
            $validated
        );


        return redirect()
            ->route('pms.guests.index')
            ->with(
                'success',
                'Guest updated successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Guest
    |--------------------------------------------------------------------------
    */
    public function destroy(Guest $guest)
    {
        $property = Property::firstOrFail();


        abort_if(
            $guest->property_id
                !==
            $property->id,
            404
        );


        /*
         * Phase 1:
         *
         * Cho phép delete.
         *
         * Sau khi có Reservation,
         * Guest có lịch sử booking
         * sẽ không được xóa cứng.
         */
        $guest->delete();


        return redirect()
            ->route('pms.guests.index')
            ->with(
                'success',
                'Guest deleted successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */
    private function validateGuest(
        Request $request,
        ?Guest $guest = null
    ): array {

        return $request->validate([

            /*
            |--------------------------------------------------------------------------
            | Name
            |--------------------------------------------------------------------------
            */
            'first_name' => [
                'required',
                'string',
                'max:100',
            ],

            'last_name' => [
                'nullable',
                'string',
                'max:100',
            ],


            /*
            |--------------------------------------------------------------------------
            | Contact
            |--------------------------------------------------------------------------
            */
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],


            /*
            |--------------------------------------------------------------------------
            | Nationality
            |--------------------------------------------------------------------------
            */
            'nationality' => [
                'nullable',
                'string',
                'max:10',
            ],


            /*
            |--------------------------------------------------------------------------
            | Date of Birth
            |--------------------------------------------------------------------------
            */
            'date_of_birth' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],


            /*
            |--------------------------------------------------------------------------
            | Gender
            |--------------------------------------------------------------------------
            */
            'gender' => [
                'nullable',

                Rule::in([
                    'male',
                    'female',
                    'other',
                    'unspecified',
                ]),
            ],


            /*
            |--------------------------------------------------------------------------
            | Identity Document
            |--------------------------------------------------------------------------
            */
            'id_type' => [
                'nullable',

                Rule::in([
                    'national_id',
                    'passport',
                    'driver_license',
                    'other',
                ]),
            ],

            'id_number' => [
                'nullable',
                'string',
                'max:100',
            ],


            /*
            |--------------------------------------------------------------------------
            | Address
            |--------------------------------------------------------------------------
            */
            'address' => [
                'nullable',
                'string',
                'max:1000',
            ],


            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */
            'status' => [
                'required',

                Rule::in([
                    'active',
                    'inactive',
                    'blacklisted',
                ]),
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Generate Guest Code
    |--------------------------------------------------------------------------
    |
    | Example:
    |
    | GST_000001
    | GST_000002
    | GST_000003
    |
    */
    private function generateGuestCode(
        Property $property
    ): string {

        /*
        |--------------------------------------------------------------------------
        | Get latest Guest Code
        |--------------------------------------------------------------------------
        */
        $latestGuest = Guest::where(
            'property_id',
            $property->id
        )
            ->where(
                'code',
                'like',
                'GST_%'
            )
            ->orderByDesc('id')
            ->first();


        /*
        |--------------------------------------------------------------------------
        | Default sequence
        |--------------------------------------------------------------------------
        */
        $nextNumber = 1;


        /*
        |--------------------------------------------------------------------------
        | Existing Guest
        |--------------------------------------------------------------------------
        */
        if ($latestGuest) {

            $number = (int) str_replace(
                'GST_',
                '',
                $latestGuest->code
            );


            $nextNumber =
                $number + 1;
        }


        /*
        |--------------------------------------------------------------------------
        | Format
        |--------------------------------------------------------------------------
        */
        return 'GST_'
            . str_pad(
                (string) $nextNumber,
                6,
                '0',
                STR_PAD_LEFT
            );
    }
}