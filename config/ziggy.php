<?php

return [

    // Route names the storefront's JavaScript never needs; keep them out of the page source.
    'except' => [
        'filament.*',
        'livewire.*',
        'telescope*',
        'api.admin.*',
        'install.*',
        'scramble.*',
        'storage.*',
        'ignition.*',
    ],

];
