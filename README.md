# MMM WordPress Boilerplate

This is a boilerplate for a model-view-controller (MVC) theme for WordPress, based on the Timber framework. 

## Installation

In the below, change `[dirname]` to your desired folder name.

1. Follow the instructions in Local WP to create a local site.
2. Ensure that ACF Pro is installed 
3. In a terminal window, navigate into `public/wp-content/themes`. 
4. Run `gh repo clone mcguinnessmedia/mmm-timber-boilerplate [dirname]`. 
5. Navigate to the new directory and change the name of the theme to your desired name in `style.css`, `package.json`, and `composer.json`.
6. Run `composer install` and `npm install --include=dev`.
7. Using the local instance, open the WP admin panel, navigate to Appearance/Themes, and set this theme as the active theme.

## Dependencies

### WP plugins

- ACF Pro ([documentation](https://www.advancedcustomfields.com/resources/))

### Composer

- Timber ([documentation](https://timber.github.io/docs/v2/))
- Twig ([documentation](https://twig.symfony.com/doc/3.x/))
- ACF Builder ([documentation](https://github.com/StoutLogic/acf-builder/wiki), [cheat sheet](https://github.com/Log1x/acf-builder-cheatsheet))

### NPM

- Vite ([website](https://vite.dev/))
- SCSS ([documentation](https://sass-lang.com/documentation/))

### Optional includes (as needed)

- Swiper ([documentation](https://swiperjs.com/get-started))