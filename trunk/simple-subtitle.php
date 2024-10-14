<?php
/**
 * Plugin Name: Simple Subtitle
 * Description: Ajoute un sous-titre à chaque article dans WordPress.
 * Version: 1.0
 * License: GPLv2 or later
 * Author: Ton nom
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

// Fonction pour charger les styles du plugin
function simple_subtitle_enqueue_styles() {
    // Vérifie si on est sur une page d'un article (post) ou autre contenu
    if ((is_singular('post') || is_singular('page'))) {
        // Enregistre et charge le fichier CSS
        wp_enqueue_style(
            'simple-subtitle-style', // Handle du style
            plugin_dir_url(__FILE__) . 'css/simple-subtitle.css', // URL vers le fichier CSS
            array(), // Dépendances (s'il y en a)
            '1.0' // Version du fichier CSS
        );
    }
}
add_action('wp_enqueue_scripts', 'simple_subtitle_enqueue_styles');

// Ajouter un sous-titre à chaque article ou page
function simple_subtitle_before_title($title, $id) {
    // Vérifier si nous sommes sur un post ou une page, et que nous ne sommes pas dans un widget
    if ((is_singular('post') || is_singular('page')) && in_the_loop() && is_main_query()) {
        // Vérifiez si nous ne sommes pas dans un widget
        if (!is_admin() && !is_feed() && !is_home() && !is_front_page() && !is_archive() && !is_search() && !is_category() && !is_tag() && !is_tax()) {
            // Récupérer le sous-titre
            $subtitle = get_post_meta($id, '_simple_subtitle', true);
            if ($subtitle) {
                // Ajouter le sous-titre après le titre
                $title .= '<h2 class="simple-subtitle">' . esc_html($subtitle) . '</h2>';
            }
        }
    }
    return $title;
}
add_filter('the_title', 'simple_subtitle_before_title', 10, 2);

// Ajouter un champ de sous-titre dans l'éditeur d'articles
function simple_subtitle_add_meta_box() {
    add_meta_box(
        'simple_subtitle_meta_box', // ID
        'Sous-titre', // Titre de la meta box
        'simple_subtitle_meta_box_callback', // Fonction de rappel
        ['post', 'page'], // Type de post (article)
        'normal', // Position
        'high' // Priorité
    );
}
add_action('add_meta_boxes', 'simple_subtitle_add_meta_box');

// Afficher le champ de sous-titre dans la meta box
function simple_subtitle_meta_box_callback($post) {
    // Récupérer la valeur existante du sous-titre
    $subtitle = get_post_meta($post->ID, '_simple_subtitle', true);
    
    // Ajouter un nonce pour sécuriser la requête
    wp_nonce_field('simple_subtitle_save_meta_box_data', 'simple_subtitle_meta_box_nonce');
    ?>
    <label for="simple_subtitle_field">Sous-titre :</label>
    <input type="text" id="simple_subtitle_field" name="simple_subtitle_field" value="<?php echo esc_attr($subtitle); ?>" style="width:100%;" />
    <?php
}

// Enregistrer le sous-titre lors de la sauvegarde de l'article
function simple_subtitle_save_postdata($post_id) {
    // Vérifier si le nonce est défini et valide
    if (!isset($_POST['simple_subtitle_meta_box_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['simple_subtitle_meta_box_nonce'])), 'simple_subtitle_save_meta_box_data')) {
        return;
    }

    // Vérifier si c'est un autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Vérifier les permissions de l'utilisateur
    if (isset($_POST['post_type']) && in_array($_POST['post_type'], ['post', 'page'])) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    } else {
        return;
    }

    // Vérifier si le champ existe dans $_POST
    if (!isset($_POST['simple_subtitle_field'])) {
        return;
    }

    // Sanitizer la valeur du sous-titre
    $subtitle = sanitize_text_field(wp_unslash($_POST['simple_subtitle_field']));

    // Mettre à jour le meta
    if ($subtitle) {
        update_post_meta($post_id, '_simple_subtitle', $subtitle);
    } else {
        delete_post_meta($post_id, '_simple_subtitle'); // Supprimer si vide
    }
}
add_action('save_post', 'simple_subtitle_save_postdata');
