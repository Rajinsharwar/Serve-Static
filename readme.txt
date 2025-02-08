=== Serve Static - Automatic WordPress Static Page generator ===
Contributors: rajinsharwar
Tags: cache, caching, performance, WP cache, Serve Static, html, static site, static website generator
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.4
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Serve Static is a static HTML page generator WordPress plugin to create and serve static copies of your existing web pages to avoid PHP/DB load.

== Description ==

Serve Static provides a very efficient, simple and fast way of implementing static page caching in WordPress.
It will generate static HTML copies of your webpages which will be then served using your server rewrite rules. This feature will bypass the whole PHP process and render only a simple HTML file without having to interect with the PHP, or MySQL for getting your page's design or content.

This plugin is really handy and helpful if your webpages are mainly static, like websites of Blogs, portfolios, company portfolios, service, and many more. This plugin doesn't support any dynamic content caching, that means, all none of your dynamic content will be showing in the frontend, rather, a static version of that content will be showing.

This plugin also comes with home-made HTML, CSS and JS minification features, which you can use to auto-minify the static files. This ensures more lightness of the HTML pages, and more boost to your page speed. Also, you can auto-warm the cache using the Warm feature, which will stimulate a visit on all your pages, to create the static files before they are served to your visitors.

== Usage ==

After activating the plugin, it will try to modify your .htaccess file. If this is not possible for some reason, make sure to enter the rules by yourself. NOTE: without these .htaccess rules, the static files won't be served or created. Note that, this is only applicable if you are using a Apache/Litespeed server.

    # BEGIN Serve Static Cache
    RewriteEngine On
    RewriteBase /
    RewriteCond %{HTTP_COOKIE} !(^|;\s*)wordpress_logged_in_.*$ [NC]
    RewriteCond %{REQUEST_URI} !^/(elementor|vc_row|fl_builder|fl-theme-builder) [NC]
    RewriteCond %{REQUEST_URI} !^/wp-admin/ [NC]
    RewriteCond %{REQUEST_METHOD} GET
    RewriteCond %{QUERY_STRING} ^$ [NC]
    RewriteCond %{DOCUMENT_ROOT}/wp-content/serve-static-cache/$1/index.html -f
    RewriteRule ^(.*)$ /wp-content/serve-static-cache/$1/index.html [L]
    # END Serve Static Cache

If you the website installed on a sub-folder, like such that "https://test.com/domain1", is your main domain of your WordPress site, you need to use a different .htaccess code. The plugin will automatically do that for you, but incase you need to do it manually, below is the format you need to follow.

    # BEGIN Serve Static Cache
    RewriteEngine On
    RewriteBase /
    RewriteCond %{HTTP_COOKIE} !(^|;\s*)wordpress_logged_in_.*$ [NC]
    RewriteCond %{REQUEST_URI} !^/(elementor|vc_row|fl_builder|fl-theme-builder) [NC]
    RewriteCond %{REQUEST_URI} !^/wp-admin/ [NC]
    RewriteCond %{REQUEST_METHOD} GET
    RewriteCond %{QUERY_STRING} ^$ [NC]
    RewriteCond "WP_CONTENT_DIR"/serve-static-cache/"sub-folder domain without slashes"/$1/index.html -f
    RewriteRule ^(.*)$ /"sub-folder domain without slashes"/wp-content/serve-static-cache/"sub-folder domain without slashes"/$1/index.html [L]
    # END Serve Static Cache

The value of the WP_CONTENT_DIR should be something like: "/home/test.com/public_html/staging/wp-content"
The value of the "sub-folder domain without slashes" should be your folder where WordPress is installed. So, if the WordPress is installed in "https://test.com/staging", you should enter "staging".

When using a nginx server, make sure to add the following rules:

    # BEGIN Serve Static Cache
    location / {
        if ($http_cookie !~* "wordpress_logged_in_") {
            set $cache_uri $request_uri;
    
            if ($request_uri ~* "^/(elementor|vc_row|fl_builder|fl-theme-builder)") {
                set $cache_uri "null cache";
            }
    
            if ($request_uri ~* "^/wp-admin/") {
                set $cache_uri "null cache";
            }
    
            if ($request_method = GET) {
                set $cache_uri "null cache";
            }
    
            if (-f $document_root/wp-content/serve-static-cache$cache_uri/index.html) {
                set $cache_file $document_root/wp-content/serve-static-cache$cache_uri/index.html;
            }
    
            if ($cache_file) {
                rewrite ^ /wp-content/serve-static-cache$cache_uri/index.html break;
            }
        }
    }
    # END Serve Static Cache

This plugin creates static HTML versions of your pages/posts, or literally any custom post types, and serves them to your non-logged-in visitors. This is an awesome way to make your website blazing fast, and not even one request is made to PHP to request your pages.

Anytime a Static page/post/any custom post type is updated, the cache of that specific page is automatically cleared, and regenerated. So, you do not have to worry about regenerating the cache eac time after making changes to your content.

This plugin is also well-integrated with frontend post rating plugins as well, so that when any rating is added, the cache gets regenerated automatically. If you are using any rating plugins that are not working with this plugin, kindly let me know in the support forum.

This plugin heavily relies on CRON to process it's functionalities. So please make sure either your server-level cron or WordPress Cron is running and working. If not, this plugin will show errors in the Admin Notices to help you direct to the problem. If there are still many issues, kindly share in the plugin support thread.

By default, this plugin automatically works with Apache and Litespeed servers, and everywhere .htaccess rules is functional. But to make this plugin work with NGINX, you will be needing to add some rules to your nginx.conf or site.conf file. An appropiate admin notice will be shown to you accordingly, kindly follow those instructions.

This plugin is supposed to work with all the form builder plugins like WP Forms, Ninja Forms. If you face any issues while using any form plugin, kindly let me know in the thread, and I will try to make it compitable.

= Performance =

Converting your website to a static webpage can drastically improve your performance and page speed. Serve Static eliminates all requests made to your database and ultimately reducing the first time to byte, total blocking time and many more.

This is especially impactful for websites that have mainly static pages, and use long pages with a lot of graphs, sliders, and other load-heavy resources like animations.

= Reduce hosting bandwidth =

Using Serve Static to serve static HTML pages of your website really cuts a great cost in your bandwidth usage, as this doesn't hit the MySQL server for your visitors, but instead serves delivers a cached, minified HTML copy. This releases much of your cost, alongside the page boost. 

We are working on features like "hosting your Static pages in a third party CDN, and serving those to your visitors". If you are interested to see this feature in soon, let me know by opening a supprt thread!

== NOTE ==

Caching is fully disabled for Administrators, or any logged-in users. Static Cache will only be served to logged-out visitors of your site.

Note that, the Static Cache can only be regenerated by using the buttons in the admin toolbar, or in the Settings page. After the cache is Flushed, the cache is NOT regenerated when someone visits the pages. This is done so that none of the personalized content gets saved in the HTML caches.

This plugin may not work as expected with a caching plugin like WP Rocket or W3 Total Cache. So make sure the URLs of the static pages are excluded from the specific plugins.
For example, when using WP Rocket, you need to navigate under Settings > WP Rocket > Advanced Rules > Never Cache URLs, and enter the URLs to the pages you want to serve as Static.
When using W3 Total Cache, navigate under Performance > Page cache > Advanced > Never cache the following pages.

== Frequently Asked Questions ==

= What does Serve Static do? =

Serve Static generates static (HTML) copies of your WordPress pages. And then serves them to your visitors instead of requesting anything from PHP or MySQL for the page content. It works a bit like a web crawler, starting at the main page of your website and looking for links to all other published pages/posts/custom posts etc to create static HTML copies of. It also includes any CSS & JS files as well.

= Who should use Serve Static? =

Pretty much everyone who has even one Static page in their website. We beilive there isn't any much need of requesting to PHP for static pages.

= Who should not use Serve Static? =

If someone bielives they have all the pages as dynamic in their website, then this plugin is not for them. This is fully made for Static webpages, and with dynamic content, it does not work in anyway. Also, a website that heavily relies on ajax to update content in real-time, isn't a good fit with this plugin. We are brainstorming some ways we can get this convered, so feel free to share your thoughts with us in a support thread!

= How do I set up Serve Static? =

Simply install and activate the plugin, and configure it's settings, and you will be having Static pages on your website served! We do not want you to think technical, so we made it the most user-friendly possible. Your suggestions are always welcomed!

= Will this plugin interfere with other caching plugins? =

Well, if configured correctly, it will not interfere with any caching plugins! We do have features like HTML minify, CSS and JS Minify. So do not enable those options if you have enabled these minifications from any other caching plugins. For any reports of incompitability, please post in support thread! I will try to update the plugin accordingly to ensure maximum compitability.

Serve Static creates a static copy of your website, which is just a collection of files: HTML, CSS, and JS. Any functionality that requires PHP code will not work with that static version. That includes, but is not limited to: blog post comments, contact forms, forums, membership areas, and eCommerce.

= How is Serve Static different from cache plugins? =

Cache plugins -- such as W3 Total Cache or WP Super Cache or WP Rocket -- make your existing WordPress site faster by caching pages as they're visited. This makes your site much faster but still makes that call to PHP or MySQL. This plugin ensures some call to PHP or MySQL is made to get the page content.

Serve Static creates a static copy of your WordPress pages that is intended to be used completely separately from your WordPress installation, PHP or MySQL.

= Can I enable cache save for logged-in users? =

Caching is by default completely disabled for Loggedin users because of compatibility issues. Still if you would like to enable caching, we have a couple of filters for that.

Use "serve_static_enable_logged_in" filter to enable cache save as a logged-in user. By default as an Administrator.
Example:
    add_filter( 'serve_static_enable_logged_in', function( $value ) {
        return true;
    } );

Use "serve_static_logged_in_role" filter to change the type of user used to generate the cache as logged-in. This filter won't work if you don't have the used the "serve_static_enable_logged_in" filter already. By default, it's value is Administrator.
Example to change the cache generation as a logged-in Subscriber user:
    add_filter( 'serve_static_logged_in_role', function( $value ) {
        return 'Subscriber';
    } );

Check this link to find the Text's you can use. Not applicable if you have custom User role: https://wordpress.org/documentation/article/roles-and-capabilities/#summary-of-roles

== Installation ==

1. Unzip the downloaded package.
2. Upload `serve-static.zip` to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Optional: You may need to modify the rewrite rules.

== Changelog ==

= 2.4 =
* Added new filters "serve_static_enable_logged_in" and "serve_static_logged_in_role" to enable cache for logged-in users.
* Fixed issue with Translations warning.
* Fixed issue with not being able to create cache folder.

= 2.3 =
* Fixed fatal errors for PHp 7.4.30.
* Fixed issue with undefined varibale in migration.
