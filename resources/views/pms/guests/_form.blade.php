<div class="space-y-6">

    {{-- PERSONAL INFORMATION --}}
    <div>

        <div class="text-[10px] uppercase tracking-wide text-[#8b949d] font-medium mb-4">
            Personal Information
        </div>


        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            {{-- FIRST NAME --}}
            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    First Name
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="first_name"
                    value="{{ old('first_name', $guest->first_name ?? '') }}"
                    class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px] outline-none focus:border-[#1677ff]"
                >

            </div>


            {{-- LAST NAME --}}
            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Last Name
                </label>

                <input
                    type="text"
                    name="last_name"
                    value="{{ old('last_name', $guest->last_name ?? '') }}"
                    class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px] outline-none focus:border-[#1677ff]"
                >

            </div>


            {{-- DOB --}}
            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Date of Birth
                </label>

                <input
                    type="date"
                    name="date_of_birth"
                    value="{{ old('date_of_birth', isset($guest) && $guest->date_of_birth ? $guest->date_of_birth->format('Y-m-d') : '') }}"
                    class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

            </div>


            {{-- GENDER --}}
            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Gender
                </label>

                <select
                    name="gender"
                    class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

                    <option value="">
                        Select
                    </option>

                    <option value="male" @selected(old('gender', $guest->gender ?? '') === 'male')>
                        Male
                    </option>

                    <option value="female" @selected(old('gender', $guest->gender ?? '') === 'female')>
                        Female
                    </option>

                    <option value="other" @selected(old('gender', $guest->gender ?? '') === 'other')>
                        Other
                    </option>

                    <option value="unspecified" @selected(old('gender', $guest->gender ?? '') === 'unspecified')>
                        Unspecified
                    </option>

                </select>

            </div>


            {{-- NATIONALITY --}}
            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Nationality
                </label>

                <input
                    type="text"
                    name="nationality"
                    value="{{ old('nationality', $guest->nationality ?? '') }}"
                    placeholder="VN"
                    maxlength="10"
                    class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

            </div>


            {{-- STATUS --}}
            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Status
                    <span class="text-red-500">*</span>
                </label>

                <select
                    name="status"
                    class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

                    <option value="active" @selected(old('status', $guest->status ?? 'active') === 'active')>
                        Active
                    </option>

                    <option value="inactive" @selected(old('status', $guest->status ?? 'active') === 'inactive')>
                        Inactive
                    </option>

                    <option value="blacklisted" @selected(old('status', $guest->status ?? 'active') === 'blacklisted')>
                        Blacklisted
                    </option>

                </select>

            </div>

        </div>

    </div>


    {{-- CONTACT --}}
    <div class="pt-5 border-t border-[#edf0f2]">

        <div class="text-[10px] uppercase tracking-wide text-[#8b949d] font-medium mb-4">
            Contact Information
        </div>


        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Phone
                </label>

                <input
                    type="text"
                    name="phone"
                    value="{{ old('phone', $guest->phone ?? '') }}"
                    placeholder="0901234567"
                    class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

            </div>


            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    value="{{ old('email', $guest->email ?? '') }}"
                    placeholder="guest@example.com"
                    class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

            </div>


            <div class="md:col-span-2">

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Address
                </label>

                <textarea
                    name="address"
                    rows="3"
                    class="w-full px-3 py-3 border border-[#dce1e6] rounded-[3px] text-[12px] resize-y"
                >{{ old('address', $guest->address ?? '') }}</textarea>

            </div>

        </div>

    </div>


    {{-- DOCUMENT --}}
    <div class="pt-5 border-t border-[#edf0f2]">

        <div class="text-[10px] uppercase tracking-wide text-[#8b949d] font-medium mb-4">
            Identification
        </div>


        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Document Type
                </label>

                <select
                    name="id_type"
                    class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

                    <option value="">
                        Select
                    </option>

                    <option value="national_id" @selected(old('id_type', $guest->id_type ?? '') === 'national_id')>
                        National ID / CCCD
                    </option>

                    <option value="passport" @selected(old('id_type', $guest->id_type ?? '') === 'passport')>
                        Passport
                    </option>

                    <option value="driver_license" @selected(old('id_type', $guest->id_type ?? '') === 'driver_license')>
                        Driver License
                    </option>

                    <option value="other" @selected(old('id_type', $guest->id_type ?? '') === 'other')>
                        Other
                    </option>

                </select>

            </div>


            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Document Number
                </label>

                <input
                    type="text"
                    name="id_number"
                    value="{{ old('id_number', $guest->id_number ?? '') }}"
                    class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

            </div>

        </div>

    </div>


    {{-- NOTES --}}
    <div class="pt-5 border-t border-[#edf0f2]">

        <label class="block text-[10px] font-medium text-[#59636e] mb-2">
            Internal Notes
        </label>

        <textarea
            name="notes"
            rows="4"
            placeholder="Preferences, late arrival, returning guest..."
            class="w-full px-3 py-3 border border-[#dce1e6] rounded-[3px] text-[12px] resize-y"
        >{{ old('notes', $guest->notes ?? '') }}</textarea>

    </div>

</div>


@if($errors->any())

    <div class="mt-5 px-4 py-3 bg-[#fff3f3] border border-[#f1cccc] rounded-[3px]">

        @foreach($errors->all() as $error)

            <div class="text-[10px] text-[#b64646]">
                {{ $error }}
            </div>

        @endforeach

    </div>

@endif