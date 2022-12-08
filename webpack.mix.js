let mix = require('laravel-mix');

mix.disableNotifications()
mix.js('resources/js/app.js', 'dist').setPublicPath('dist');
mix.less('resources/less/app.less', 'public/css');
