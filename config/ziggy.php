<?php

return [

    // Route names the storefront's JavaScript never needs; keep them out of the page source.
    'except' => [
        'filament.*',
        'livewire.*',
        'debugbar.*',
        'telescope*',
        'storage.*',
        'ignition.*',
    ],

];
