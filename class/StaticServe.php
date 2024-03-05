<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Initialize the WP_Filesystem
if ( ! function_exists( 'WP_Filesystem' ) ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}

WP_Filesystem();

require_once __DIR__.'/minify/minifyJS.php';

class StaticServe {

    public function Build(){
        
        if ( ! isset( $_SERVER['HTTP_X_SERVE_STATIC_REQUEST'] ) || $_SERVER['HTTP_X_SERVE_STATIC_REQUEST'] !== 'true' ) {
            return false; // Return early if the request is not from SendRequest()
        }

        /**
         * Processes the building of HTMl files.
         * @writes-to-file
         * 
         * $is_all makes 1+1 requests.
         * $is_post_types makes (1)+1+1+1+1 requests.
         * $is_manual makes (1)+(1)+1+1 requests.
         */

        //Build the HTML, CSS and JS, and save in local.
        $current_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        if ( get_option( 'serve_static_make_static' ) == 1 ){
            $excluded_urls = get_option('serve_static_exclude_urls', array());
            if ( isset($excluded_urls[$current_url]) ){
                return false;
            }

        } elseif ( get_option( 'serve_static_post_types_static' ) == 1 ){
            $excluded_urls = get_option('serve_static_exclude_urls', array());
            if ( isset($excluded_urls[$current_url]) ){
                return false;
            }

            $haystack = get_option('serve_static_specific_post_types', array());
            $needle = get_post_type(url_to_postid($current_url));
            if ( ! isset( $haystack[ $needle ] ) ){
                return false;
            }

        } elseif ( get_option( 'serve_static_manual_entry' ) == 1 ){
            $serving_static = get_option('serve_static_urls', array());
            if ( ! isset( $serving_static[ $current_url ] ) ){
                return false;
            }
        }
        $html_path = WP_CONTENT_DIR . '/html-cache' . wp_parse_url($current_url, PHP_URL_PATH) . '/index.html';
        if ( ! file_exists( $html_path ) ) {
            // If HTML copy doesn't exist, create one
            ob_start(function($html_content) use ($current_url, $html_path) {
                if (!empty($html_content)) {
                    $date = (new \DateTime)->format('c');
                    $html_content = $this->Cache($html_content);
                    // Compress HTML content
                    if (get_option( 'serve_static_html_minify_enabled' ) == 1 ){
                        $html_content = $this->minifyHTML($html_content);
                    }

                    $critical_css = $this->identify_critical_css($html_content);
                    $html_content = $this->inline_critical_css($html_content, $critical_css);

                    // Save modified HTML content directly to a file
                    $html_content .= PHP_EOL . "<!-- Cached by Serve Static - https://profiles.wordpress.org/rajinsharwar - Last modified: {$date} -->";

                    global $wp_filesystem;

                    // Check if the WP_Filesystem is initialized
                    if ( ! is_wp_error( $wp_filesystem ) ) {
                        $directory = dirname( $html_path );

                        // Create directory if it doesn't exist
                        if ( ! $wp_filesystem->is_dir( $directory ) ) {
                            wp_mkdir_p($directory); //Here it doesn't work if there is 2d directory, like product-category/apples
                        }

                        // Write HTML content to the file
                        if ( $wp_filesystem->put_contents( $html_path, $html_content, FS_CHMOD_FILE ) ) {
                            exit;
                        }
                    }
                }
            });
        }
    }

    /**
     * Identifies critical CSS in the HTML DOM.
     * @param string $html The HTML content to analyze.
     * @return string Critical CSS styles found in the HTML.
     */
    public function identify_critical_css($html) {
        $critical_css = '';

        // Load HTML content into DOMDocument
        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // Suppress warnings
        $dom->loadHTML($html);
        libxml_clear_errors();

        // XPath query to find elements within the initial viewport
        $xpath = new DOMXPath($dom);
        $viewport_elements = $xpath->query('//*[not(self::script) and not(self::noscript) and not(self::style)][@style or @id or @class][ancestor-or-self::*[@style or @id or @class][contains(@style, "position:fixed") or contains(@style, "position:absolute")]]');

        // Extract CSS from matched elements
        foreach ($viewport_elements as $element) {
            // Get computed styles for the element
            $computed_styles = window.getComputedStyle($element);

            // Extract relevant CSS rules from computed styles
            $critical_css .= $computed_styles->cssText;
        }

        return $critical_css;
    }

    /**
     * Inlines critical CSS into the HTML DOM.
     * @param string $html The HTML content to inline critical CSS into.
     * @param string $critical_css The critical CSS styles to inline.
     * @return string The HTML content with critical CSS inlined.
     */
    public function inline_critical_css($html, $critical_css) {
        // Insert critical CSS into style element in the head
        $dom = new DOMDocument();
        $dom->loadHTML($html);

        $head = $dom->getElementsByTagName('head')->item(0);
        $style = $dom->createElement('style');
        $style->setAttribute('type', 'text/css');
        $style->nodeValue = $critical_css;

        // Check if there are existing style elements, and insert before them
        $existing_styles = $dom->getElementsByTagName('style');
        if ($existing_styles->length > 0) {
            $existing_style = $existing_styles->item(0);
            $head->insertBefore($style, $existing_style);
        } else {
            $head->appendChild($style);
        }

        // Save modified HTML content
        $html = $dom->saveHTML();

        return $html;
    }


    public function Cache($html_content) {
        // Create folders for CSS and JS if they don't exist
        $css_dir = WP_CONTENT_DIR . '/html-cache/css';
        $js_dir = WP_CONTENT_DIR . '/html-cache/js';

        global $wp_filesystem;

        if ( ! $wp_filesystem->is_dir($css_dir)) {
            $wp_filesystem->mkdir($css_dir, 0755, true);
        }

        if ( ! $wp_filesystem->is_dir($js_dir)) {
            $wp_filesystem->mkdir($js_dir, 0755, true);
        }
    
        // Find and cache CSS links
        if ( get_option( 'serve_static_css_minify_enabled' ) == 1 ){
            preg_match_all('/<link[^>]+href=["\']([^"\']+\.css)["\'][^>]*>/', $html_content, $css_matches);
            foreach ($css_matches[1] as $css_url) {
                // $context = stream_context_create([
                //     'ssl' => [
                //         'verify_peer' => false,
                //         'verify_peer_name' => false,
                //     ],
                // ]);
                // //Minify CSS
                // $css_content = file_get_contents($css_url, false, $context);
                $context = [
                    'sslverify' => false,
                ];
                //Minify CSS
                $css_content = wp_remote_retrieve_body(wp_remote_get($css_url, $context));
                $css_content = $this->convertRelativeToAbsoluteUrls($css_content, $css_url);
                $css_content = $this->minifyCSS($css_content);
    
                $css_filename = basename($css_url);
                $css_subfolder = dirname(wp_parse_url($css_url, PHP_URL_PATH));
                $css_subfolder_path = $css_dir . $css_subfolder;

                global $wp_filesystem;

                if ( ! $wp_filesystem->is_dir($css_subfolder_path)) {
                    wp_mkdir_p($css_subfolder_path);
                }
                $wp_filesystem->put_contents( $css_subfolder_path . '/' . $css_filename, $css_content, FS_CHMOD_FILE );
                // $html_content = str_replace($css_url, $this->convertRelativeToAbsolute($css_url, WP_CONTENT_URL . '/html-cache/css' . $css_subfolder . '/' . $css_filename), $html_content);
                $html_content = str_replace($css_url, WP_CONTENT_URL . '/html-cache/css' . $css_subfolder . '/' . $css_filename, $html_content);
            }
        }
        
        // Find and cache JS links
        if ( get_option( 'serve_static_js_minify_enabled' ) == 1 ){
            preg_match_all('/<script[^>]+src=["\']([^"\']+\.js)["\'][^>]*>/', $html_content, $js_matches);
            foreach ($js_matches[1] as $js_url) {
                // $context = stream_context_create([
                //     'ssl' => [
                //         'verify_peer' => false,
                //         'verify_peer_name' => false,
                //     ],
                // ]);
                // //Minify JS
                // $js_content = file_get_contents($js_url, false, $context);
                $context = [
                    'sslverify' => false,
                ];
                $js_content = wp_remote_retrieve_body(wp_remote_get($js_url, $context));
                // $js_content = $this->minifyJS($js_content);
                $js_content = Minifier::minify($js_content);

                $js_filename = basename($js_url);
                $js_subfolder = dirname(wp_parse_url($js_url, PHP_URL_PATH));
                $js_subfolder_path = $js_dir . $js_subfolder;
                if (!is_dir($js_subfolder_path)) {
                    wp_mkdir_p($js_subfolder_path);
                }
                $wp_filesystem->put_contents($js_subfolder_path . '/' . $js_filename, $js_content, FS_CHMOD_FILE);
                $html_content = str_replace($js_url, WP_CONTENT_URL . '/html-cache/js' . $js_subfolder . '/' . $js_filename, $html_content);
                // $html_content = str_replace($js_url, $this->convertRelativeToAbsolute($js_url, WP_CONTENT_URL . '/html-cache/js' . $js_subfolder . '/' . $js_filename), $html_content);
            }
        }

        return $html_content;
    }

    public function Flush($url = null){

        global $wp_filesystem;
    
        $directory = WP_CONTENT_DIR . '/html-cache';
    
        if ($url) {
            $url_path = wp_parse_url($url, PHP_URL_PATH);
            $directory .= $url_path;
        }
    
        if (is_dir($directory)) {
    
            $url_ends_with_slash = $url && substr($url, -1) === '/';
    
            if ($url_ends_with_slash) {
                $index_file = $directory . '/index.html';
                if ($wp_filesystem->exists($index_file)) {
                    $wp_filesystem->delete($index_file);
                }
            } else {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    
                foreach ($iterator as $path) {
                    if ($path->isDir()) {
                        $wp_filesystem->rmdir($path->getPathname(), true);
                    } else {
                        $wp_filesystem->delete($path->getPathname());
                    }
                }
            }
        }
    
        delete_transient('serve_static_cache_warming_in_progress', true);
    }

    /**
     * Flush indiviual post ID
     */
    public function FlushPost( $post_id ){
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        if ( get_option( 'serve_static_post_types_static' ) == 1 ){

            $post_type = get_post_type($post_id);
            $post_types = get_option( 'serve_static_specific_post_types' );

            if ( ! isset($post_types[$post_type]) ){
                return;
            }

            $post_url = get_permalink($post_id);
            $excluded_urls = get_option( 'serve_static_exclude_urls' );
    
            $flush = new StaticServe();
            $flush->Flush($post_url);
    
            if ( $excluded_urls !== false && isset($excluded_urls[$post_url]) ){
                return;
            }
    
            $warmup = new WarmUp();
            $warmup->WarmitUpTriggers($post_url);

            return;

        } elseif ( get_option( 'serve_static_make_static' ) == 1 ){

            $post_url = get_permalink($post_id);
            $excluded_urls = get_option( 'serve_static_exclude_urls' );

            $this->Flush($post_url); //Flushing before checking for excluded urls so that we can clean the cache if it is excluded.

            if ( $excluded_urls !== false && isset($excluded_urls[$post_url]) ){
                return;
            }

            $warmup = new WarmUp();
            $warmup->WarmitUpTriggers($post_url);

            return;

        } elseif ( get_option( 'serve_static_manual_entry' ) == 1 ) {
            $post_url = get_permalink($post_id);
            $included_urls = get_option( 'serve_static_urls' );
            
            if ( isset( $included_urls[ $post_url ] )){
    
                $flush = new StaticServe();
                $flush->Flush($post_url);
        
                $warmup = new WarmUp();
                $warmup->WarmitUpTriggers($post_url);
            }

            return;
        }
    }

    public function minifyHTML($input) {

        if(trim($input) === "") return $input;
        // Remove extra white-space(s) between HTML attribute(s)
        $input = preg_replace_callback('#<([^\/\s<>!]+)(?:\s+([^<>]*?)\s*|\s*)(\/?)>#s', function($matches) {
            return '<' . $matches[1] . preg_replace('#([^\s=]+)(\=([\'"]?)(.*?)\3)?(\s+|$)#s', ' $1$2', $matches[2]) . $matches[3] . '>';
        }, str_replace("\r", "", $input));
        // Minify inline CSS declaration(s)
        if(strpos($input, ' style=') !== false) {
            $input = preg_replace_callback('#<([^<]+?)\s+style=([\'"])(.*?)\2(?=[\/\s>])#s', function($matches) {
                return '<' . $matches[1] . ' style=' . $matches[2] . $this->minifyCSS($matches[3]) . $matches[2];
            }, $input);
        }
        if(strpos($input, '</style>') !== false) {
          $input = preg_replace_callback('#<style(.*?)>(.*?)</style>#is', function($matches) {
            return '<style' . $matches[1] .'>'. $this->minifyCSS($matches[2]) . '</style>';
          }, $input);
        }
        if(strpos($input, '</script>') !== false) {
          $input = preg_replace_callback('#<script(.*?)>(.*?)</script>#is', function($matches) {
            return '<script' . $matches[1] .'>'. $this->minifyJS($matches[2]) . '</script>';
          }, $input);
        }
    
        return preg_replace(
            array(
                // t = text
                // o = tag open
                // c = tag close
                // Keep important white-space(s) after self-closing HTML tag(s)
                '#<(img|input)(>| .*?>)#s',
                // Remove a line break and two or more white-space(s) between tag(s)
                '#(<!--.*?-->)|(>)(?:\n*|\s{2,})(<)|^\s*|\s*$#s',
                '#(<!--.*?-->)|(?<!\>)\s+(<\/.*?>)|(<[^\/]*?>)\s+(?!\<)#s', // t+c || o+t
                '#(<!--.*?-->)|(<[^\/]*?>)\s+(<[^\/]*?>)|(<\/.*?>)\s+(<\/.*?>)#s', // o+o || c+c
                '#(<!--.*?-->)|(<\/.*?>)\s+(\s)(?!\<)|(?<!\>)\s+(\s)(<[^\/]*?\/?>)|(<[^\/]*?\/?>)\s+(\s)(?!\<)#s', // c+t || t+o || o+t -- separated by long white-space(s)
                '#(<!--.*?-->)|(<[^\/]*?>)\s+(<\/.*?>)#s', // empty tag
                '#<(img|input)(>| .*?>)<\/\1>#s', // reset previous fix
                '#(&nbsp;)&nbsp;(?![<\s])#', // clean up ...
                '#(?<=\>)(&nbsp;)(?=\<)#', // --ibid
                // Remove HTML comment(s) except IE comment(s)
                '#\s*<!--(?!\[if\s).*?-->\s*|(?<!\>)\n+(?=\<[^!])#s'
            ),
            array(
                '<$1$2</$1>',
                '$1$2$3',
                '$1$2$3',
                '$1$2$3$4$5',
                '$1$2$3$4$5$6$7',
                '$1$2$3',
                '<$1$2',
                '$1 ',
                '$1',
                ""
            ),
        $input);
    }

    // Function to minify CSS
    private function minifyCSS($input) {
        if(trim($input) === "") return $input;
        return preg_replace(
            array(
                // Remove comment(s)
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
                // Remove unused white-space(s)
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~]|\s(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
                // Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
                '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
                // Replace `:0 0 0 0` with `:0`
                '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
                // Replace `background-position:0` with `background-position:0 0`
                '#(background-position):0(?=[;\}])#si',
                // Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
                '#(?<=[\s:,\-])0+\.(\d+)#s',
                // Minify string value
                '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
                '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
                // Minify HEX color code
                '#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
                // Replace `(border|outline):none` with `(border|outline):0`
                '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
                // Remove empty selector(s)
                '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s'
            ),
            array(
                '$1',
                '$1$2$3$4$5$6$7',
                '$1',
                ':0',
                '$1:0 0',
                '.$1',
                '$1$3',
                '$1$2$4$5',
                '$1$2$3',
                '$1:0',
                '$1$2'
            ),
        $input);
    }

    // Function to minify JS
    private function minifyJS($input) {
        if(trim($input) === "") return $input;
        return preg_replace(
            array(
                // Remove comment(s)
                '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',
                // Remove white-space(s) outside the string and regex
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',
                // Remove the last semicolon
                '#;+\}#',
                // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
                '#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
                // --ibid. From `foo['bar']` to `foo.bar`
                '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i'
            ),
            array(
                '$1',
                '$1$2',
                '}',
                '$1$3',
                '$1.$3'
            ),
        $input);
    }

    private function convertRelativeToAbsoluteUrls($css_content, $css_url) {
        // Parse the base URL of the CSS file
        $base_url = dirname($css_url) . '/';
    
        // Use regular expressions to replace relative URLs with absolute URLs
        $css_content = preg_replace_callback('/url\([\'"]?([^\'"\)]+)[\'"]?\)/i', function($matches) use ($base_url) {
            $url = trim($matches[1]);
    
            // Check if the URL is already absolute
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                return "url('$url')";
            }
    
            // Convert the relative URL to an absolute URL
            $absolute_url = $base_url . $url;
    
            return "url('$absolute_url')";
        }, $css_content);
    
        return $css_content;
    }

    private function convertRelativeToAbsoluteUrls1($content, $base_url) {
        // Parse the base URL
        $base_url = dirname($base_url) . '/';
    
        // Use regular expressions to replace relative URLs with absolute URLs
        $content = preg_replace_callback('/(src|href)=["\']?([^"\'\s>]+)["\']?/i', function($matches) use ($base_url) {
            $url = trim($matches[2]);
    
            // Check if the URL is already absolute
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                return $matches[1] . '="' . $url . '"';
            }
    
            // Convert the relative URL to an absolute URL
            $absolute_url = $base_url . $url;
    
            return $matches[1] . '="' . $absolute_url . '"';
        }, $content);
    
        return $content;
    }

    public function is_cache_available( $url ){

        global $wp_filesystem;
    
        $directory = WP_CONTENT_DIR . '/html-cache';
        $url_path = wp_parse_url($url, PHP_URL_PATH);
        $directory .= $url_path;
    
        if (is_dir($directory)) {
    
            $index_file = $directory . '/index.html';
            if ($wp_filesystem->exists($index_file)) {
                return true;
            } else {
                return false;
            }
        }
    }
}

require_once( ABSPATH . '/wp-includes/pluggable.php' );
//Register Static service
$static = new StaticServe();

if ( ! is_admin() && ! is_user_logged_in() && ! strpos($_SERVER['REQUEST_URI'], 'elementor') !== false && get_option('serve_static_master_key', '') != '' && get_option( 'serve_static_master_key' ) == 1 ){
    add_action('template_redirect', array( $static, 'Build' ));
}