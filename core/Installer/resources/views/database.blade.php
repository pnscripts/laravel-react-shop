@extends('pnshop-installer::layout', ['step' => 1])

@section('content')
    @if ($configCached)
        <p class="error">The configuration is cached. Run <code>php artisan config:clear</code> first, or the database settings cannot take effect.</p>
    @endif
    <form method="post" action="{{ url('/install/database') }}">
        @csrf
        <label for="connection">Database</label>
        <select id="connection" name="connection">
            @foreach (['mysql' => 'MySQL', 'mariadb' => 'MariaDB', 'pgsql' => 'PostgreSQL', 'sqlite' => 'SQLite (a file; small shops and testing)'] as $value => $label)
                <option value="{{ $value }}" @selected(old('connection', $values['connection']) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('connection') <p class="error">{{ $message }}</p> @enderror
        <p class="muted">For MySQL, MariaDB and PostgreSQL, create an empty database and a user for it first. SQLite needs nothing.</p>
        <div class="row">
            <div><label for="host">Host</label><input id="host" name="host" value="{{ old('host', $values['host']) }}">@error('host') <p class="error">{{ $message }}</p> @enderror</div>
            <div><label for="port">Port</label><input id="port" name="port" inputmode="numeric" value="{{ old('port', $values['port']) }}">@error('port') <p class="error">{{ $message }}</p> @enderror</div>
        </div>
        <label for="database">Database name</label>
        <input id="database" name="database" value="{{ old('database', $values['database']) }}">
        @error('database') <p class="error">{{ $message }}</p> @enderror
        <div class="row">
            <div><label for="username">User</label><input id="username" name="username" autocomplete="off" value="{{ old('username', $values['username']) }}">@error('username') <p class="error">{{ $message }}</p> @enderror</div>
            <div><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="new-password"></div>
        </div>
        <button type="submit">Connect and continue</button>
    </form>
@endsection
