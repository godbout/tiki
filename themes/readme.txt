DESCRIPTION OF THEME DIRECTORIES
To make your own theme (for example, a theme called "ABC"), the layout of the files is:

CSS files:
themes/ABC/css/ABC.css, etc.   (After this directory and file are created, you can select the theme on http://example.com/tiki-admin.php?page=look)

SCSS:
themes/ABC/scss/ABC.scss, etc.   (Best practice is to create the theme stylesheet by compiling SCSS files, which go in this directory. )

Fonts:
themes/ABC/fonts/   (For custom fonts that are stored locally rather than imported via CSS)

Icons:
themes/ABC/icons/   (For a custom icon font set, as an option to the Font Awesome icon set that is bundled with Tiki)

Smarty template files:
themes/ABC/templates/   (For .tpl files which override same-name equivalents in templates/)

Images:
themes/ABC/images/    (For theme background images, etc.)

More details at:
https://themes.tiki.org/How+To+Add+a+New+Bootstrap+Theme
