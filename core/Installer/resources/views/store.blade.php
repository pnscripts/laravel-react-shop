@extends('pnshop-installer::layout', ['step' => 2])

@section('content')
    <p class="muted">Connected to the {{ $database }} database. Installing creates the tables; it can take a minute.</p>
    @error('install') <p class="error">{{ $message }}</p> @enderror
    <form method="post" action="{{ url('/install/store') }}">
        @csrf
        <h2>Store</h2>
        <label for="store_name">Store name</label>
        <input id="store_name" name="store_name" required value="{{ old('store_name') }}">
        @error('store_name') <p class="error">{{ $message }}</p> @enderror
        <label for="store_email">Contact email (optional)</label>
        <input id="store_email" name="store_email" type="email" value="{{ old('store_email') }}">
        @error('store_email') <p class="error">{{ $message }}</p> @enderror
        <div class="row">
            <div>
                <label for="locale">Default language</label>
                <select id="locale" name="locale">
                    <option value="en" @selected(old('locale', 'en') === 'en')>English</option>
                    <option value="bg" @selected(old('locale') === 'bg')>Български</option>
                </select>
            </div>
            <div>
                <label for="currency">Currency</label>
                <input id="currency" name="currency" maxlength="3" required value="{{ old('currency', 'EUR') }}">
                @error('currency') <p class="error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="row">
            <div>
                <label for="country">Store country (e.g. BG)</label>
                <input id="country" name="country" maxlength="2" value="{{ old('country') }}">
                @error('country') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="timezone">Timezone</label>
                <select id="timezone" name="timezone">
                    @foreach ($timezones as $zone)
                        <option value="{{ $zone }}" @selected(old('timezone', 'UTC') === $zone)>{{ $zone }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <label class="check"><input type="hidden" name="prices_include_tax" value="0"><input type="checkbox" name="prices_include_tax" value="1" @checked(old('prices_include_tax', '1') === '1')> Catalog prices include tax (usual for VAT)</label>
        <label class="check"><input type="checkbox" name="demo" value="1" @checked(old('demo'))> Add demo products</label>

        <h2>Administrator</h2>
        <p class="muted">There are no default accounts: this is the only way into the admin panel.</p>
        <label for="admin_name">Name</label>
        <input id="admin_name" name="admin_name" required value="{{ old('admin_name') }}">
        @error('admin_name') <p class="error">{{ $message }}</p> @enderror
        <label for="admin_email">Email</label>
        <input id="admin_email" name="admin_email" type="email" required value="{{ old('admin_email') }}">
        @error('admin_email') <p class="error">{{ $message }}</p> @enderror
        <div class="row">
            <div><label for="admin_password">Password</label><input id="admin_password" name="admin_password" type="password" autocomplete="new-password" required></div>
            <div><label for="admin_password_confirmation">Repeat password</label><input id="admin_password_confirmation" name="admin_password_confirmation" type="password" autocomplete="new-password" required></div>
        </div>
        @error('admin_password') <p class="error">{{ $message }}</p> @enderror
        <button type="submit">Install</button>
    </form>
@endsection
