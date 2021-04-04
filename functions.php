<?php

//add this snippet to your functions.php
//tested on Wordpress 5.6.1 with WPML 4.4.8 and Groundhogg 2.3.4.1
//WPML cookie code modified from https://tombroucke.be/blog/wpml-remember-users-language-choice/


// START WPML COOKIE AND REDIRECT MANAGEMENT FOR GROUNDHOGG
// thanks to Tom Broucke's blog post WPML: REMEMBER USER’S LANGUAGE CHOICE
// https://tombroucke.be/blog/wpml-remember-users-language-choice/

function is_valid_language( $input_lang ) {
    // Check if the switch_language variable is a registered language
    return array_key_exists( $input_lang, icl_get_languages() );
}

// Add ?switch_lang=lang to all url's in the languaqe switcher
add_filter( 'icl_ls_languages', function( $languages ){
    global $sitepress;
    foreach( $languages as $lang_code => $language ){
        $languages[$lang_code]['url'] = add_query_arg( array( 'switch_language' => $lang_code ), $languages[$lang_code]['url'] );
    }
    return $languages; 
} );

// When user switches language, update wp-wpml_user_selected_language cookie
// Otherwise, check if user is on a gh managed page and needs redirection based on their selected language cookie
add_action( 'init', function (){
    // GET variables
    $switch_language = filter_input(INPUT_GET, 'switch_language', FILTER_SANITIZE_STRING);
    $user_selected_language = filter_input( INPUT_COOKIE, 'wp-wpml_user_selected_language', FILTER_SANITIZE_STRING );

    $pages_to_redirect = ['preferences', 'calendar'];
    $url_prefix = strtok( $_SERVER['REQUEST_URI'], '/' );
    $is_gh_managed_page_in_default_lang = $url_prefix == 'gh' && in_array(strtok( '/' ), $pages_to_redirect);
    $user_lang_not_eq_current_lang = $user_selected_language != ICL_LANGUAGE_CODE ;

    $switching = $switch_language && is_valid_language($switch_language);
    $need_cookie_without_switching = $user_selected_language != $url_prefix && is_valid_language($url_prefix);
    if( $need_cookie_without_switching ) { $switch_language = $url_prefix; }

    if( $switching || $need_cookie_without_switching ) {
        // Create a cookie that never expires, technically it expires in 10 years
        setcookie( 'wp-wpml_user_selected_language', $switch_language, time() + (10 * 365 * 24 * 60 * 60), '/' );
        // Let's redirect the users to the request uri without the querystring, otherwise the server will send an uncached page
        wp_redirect( strtok( $_SERVER['REQUEST_URI'], '?' ) );
        exit;
    } elseif ( $is_gh_managed_page_in_default_lang && $user_lang_not_eq_current_lang && is_valid_language($user_selected_language) ) { 
        // We are on a groundhogg managed page and in need of a redirect to user_selected_language
        $url = "/".$user_selected_language.$_SERVER['REQUEST_URI'];
        wp_redirect( $url );
        exit;
    }
}, 1);

// END WPML COOKIE AND REDIRECT MANAGEMENT FOR GROUNDHOGG

?>
