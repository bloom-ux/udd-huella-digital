<?php
/**
 * Plugin Name: Perfiles Huella Digital UDD
 * Version: 0.1.5
 * Plugin URI: http://www.udd.cl
 * Author: Bloom User Experience
 * Author URI: https://bloom-ux.com
 * Description: Crea listas de personas a partir de información creada en el portal corporativo UDD, permite reducir esfuerzo y contenidos duplicados.
 */

define('UDD_CORPORATE_PROFILES_SHORTCODE_TAG', 'perfiles_huella_digital');

// incluir funciones
require __DIR__ .'/functions.php';

// - registrar "listas" como post type
add_action('init', 'udd_profiles_register_post_type');

/**
 * Registrar el post type personalizado
 * @return object|WP_Error El post type registrado o un error de WordPress
 */
function udd_profiles_register_post_type() {
	$post_type_args = array(
		'label'               => _x('Listas de Personas', 'people_list', 'cpt_people_list'),
		'labels'              => array(
			'name'               => _x('Listas de Personas', 'people_list', 'cpt_people_list'),
			'singular_name'      => _x('Lista de Personas', 'people_list', 'cpt_people_list'),
			'add_new'            => _x('Agregar nueva Lista de Personas Huella Digital UDD', 'people_list', 'cpt_people_list'),
			'all_items'          => _x('Huella Digital UDD', 'people_list', 'cpt_people_list'),
			'add_new_item'       => _x('Agregar nueva Lista de Personas', 'people_list', 'cpt_people_list'),
			'edit_item'          => _x('Editar Lista de Personas Huella Digital UDD', 'people_list', 'cpt_people_list'),
			'new_item'           => _x('Nueva Lista de Personas', 'people_list', 'cpt_people_list'),
			'view_item'          => _x('Ver Lista de Personas', 'people_list', 'cpt_people_list'),
			'search_items'       => _x('Buscar Listas de Personas', 'people_list', 'cpt_people_list'),
			'not_found'          => _x('No se han encontrado Listas de Personas', 'people_list', 'cpt_people_list'),
			'not_found_in_trash' => _x('No se han encontrado Listas de Personas en la papelera', 'people_list', 'cpt_people_list'),
			'parent_item_colon'  => _x('Lista de Personas padre', 'people_list', 'cpt_people_list'),
			'menu_name'          => _x('Huella Digital UDD', 'people_list', 'cpt_people_list')
		),
		'description'         => _x('', 'people_list', 'cpt_people_list'),
		'public'              => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'show_ui'             => true,
		'show_in_nav_menus'   => false,
		'show_in_menu'        => true,
		'show_in_admin_bar'   => false,
		'menu_position'       => null,
		'menu_icon'           => 'dashicons-groups',
		'map_meta_cap'        => true,
		'capability_type'     => array( 'people_list', 'people_lists' ),
		'hierarchical'        => false,
		'supports'            => array( 'title' ),
		'has_archive'         => false,
		'rewrite'             => false,
		'query_var'           => false,
		'can_export'          => true
	);
	$post_type_args = apply_filters('udd_profiles_register_post_type\post_type_args', $post_type_args );
	return register_post_type('people_list', $post_type_args );
}


add_action('admin_init', function(){
	// inicializar la administración
	require __DIR__ .'/class-admin.php';
	$admin = new UDD_Corporate_Profiles\Admin;
	$admin->init();
});

// inicializar el controlador del repositorio
require __DIR__ .'/class-repository.php';

// inicializar el shortcode de legado
if ( class_exists('GutenPress\Model\ShortcodeFactory') ) {
	require __DIR__ .'/class-legacy-shortcode.php';
	GutenPress\Model\ShortcodeFactory::create('UDD_Corporate_Profiles\Legacy_Shortcode');
} else {
	add_shortcode( UDD_CORPORATE_PROFILES_SHORTCODE_TAG, 'UDD_Corporate_Profiles\shortcode_display' );
}

// inicializar el shortcode ui
add_action('register_shortcode_ui', function(){
	require __DIR__ .'/shortcode-ui.php';
	UDD_Corporate_Profiles\register_shortcode_ui();
});