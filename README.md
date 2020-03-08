# Super PDF Plugin for Craft CMS 3

Creating PDF is easy and fast now with Super PDF. Plug, Twig and play. Super PDF is backed with DomPDF library and gives you full power to covert your twig templates into PDF. You can pass external CSS and HTML 4.0 attributes working with it. All the functionality DomPDF gives can be accessible easily in Super PDF.

### Features
 * Handles most CSS 2.1 and a few CSS3 properties, including @import, @media &
   @page rules
 * Supports most presentational HTML 4.0 attributes
 * Supports external stylesheets, either local or through http/ftp (via
   fopen-wrappers)
 * Supports complex tables, including row & column spans, separate & collapsed
   border models, individual cell styling
 * Image support (gif, png (8, 24 and 32 bit with alpha channel), bmp & jpeg)
 * No dependencies on external PDF libraries, thanks to the R&OS PDF class
 * Inline PHP support
 * Basic SVG support

### Requirements
 * PHP version 7.1 or higher
 * Craft CMS 3.0 or higher
 * DOM extension
 * MBString extension
 * php-font-lib
 * php-svg-lib

Note that some required dependencies may have further dependencies (notably php-svg-lib requires sabberworm/php-css-parser).

---
### Installation
Open your terminal and go to your Craft project:

```bash
cd /path/to/project
```
Run this command to load the plugin:

```bash
composer require amici-infotech/craft-super-pdf
```

In the Control Panel, go to Settings → Plugins and click the “Install” button for Super PDF.

---
### Usage
You can create PDF from HTML using this code:
```bash
{% set html %}
	<h1>This is a basic example</h1>
	<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod.</p>
{% endset %}

{% set settings = {
    filename: "My_PDF",
} %}

{{ craft.superpdf.html(html, settings) }}
```

You can create PDF from template using this code:
```bash
{% set settings = {
    filename: "My_PDF",
} %}

{% set vars = {
	entry : entry,
	data : data
} %}

{{ craft.superpdf.template("template/_pdf_template", settings, vars) }}
```
### Documentation
Visit the [Super PDF page](https://docs.amiciinfotech.com/craft/super-pdf) for all documentation, guides, pricing and developer resources.

### Support
Get in touch with us via the [Amici Infotech Support](https://amiciinfotech.com/contact) or by [creating a Github issue](https://github.com/amici-infotech/craft-super-pdf/issues)
