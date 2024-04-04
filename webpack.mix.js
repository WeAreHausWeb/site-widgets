// Mix examples here: https://laravel-mix.com/docs/6.0/examples
let mix = require('laravel-mix');

// Default styles
mix.sass(`assets/scss/style.scss`, `dist`).sourceMaps();
mix.sass(`assets/scss/admin.scss`, `dist`).sourceMaps();


// Default scripts
mix.js([
    'assets/js/script.js',
], 'dist/script.js');

// Scripts that require Vue
/*
mix.js([
    'src/XXX/js/script.js',
    'src/XXX/js/selection.js',
], 'dist/XXX.js').vue();
*/