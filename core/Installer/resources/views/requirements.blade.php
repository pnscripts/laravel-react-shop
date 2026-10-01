@extends('pnshop-installer::layout', ['step' => 0])

@section('content')
    <p class="muted">PN Shop checks that this server can run it.</p>
    <table>
        @foreach ($checks as $check)
            <tr>
                <td>{{ $check['label'] }}<br><span class="muted">{{ $check['detail'] }}{{ $check['required'] ? '' : ' (optional)' }}</span></td>
                <td class="{{ $check['ok'] ? 'ok' : ($check['required'] ? 'bad' : 'muted') }}">{{ $check['ok'] ? 'OK' : ($check['required'] ? 'Missing' : 'Not available') }}</td>
            </tr>
        @endforeach
    </table>
    @if ($passes)
        <a class="button" href="{{ url('/install/database') }}">Continue</a>
    @else
        <p class="error">Fix the items marked "Missing" (ask your host), then reload this page.</p>
    @endif
@endsection
