let mix = require('laravel-mix');

mix.sass('sass/style.scss' , 'css/');
mix.options({
	processCssUrls: false
});
mix.sass('../../../modules/custom/amt_dashboard/assets/css/studio_director_dashboard.sass' , '../../../modules/custom/amt_dashboard/assets/css');
mix.options({
	processCssUrls: false
});