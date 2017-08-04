<?php

namespace UDD_Corporate_Profiles;

/**
 * Registrar interfaz para el shortcode de Huella Digital
 */
function register_shortcode_ui( ) {
	$shortcode_ui_args = array(
		'label'         => esc_html('Lista de Personas Huella Digital'),
		'listItemImage' => 'dashicons-groups',
		'attrs'         => array(
			array(
				'label'    => esc_html('Seleccionar lista de personas'),
				'attr'     => 'lista',
				'type'     => 'post_select',
				'query'    => array( 'post_type' => 'people_list' ),
				'multiple' => false
			)
		)
	);
	$shortcode_ui_args = apply_filters('UDD_Corporate_Profiles\register_shortcode_ui\shortcode_ui_args', $shortcode_ui_args);
	shortcode_ui_register_for_shortcode( UDD_CORPORATE_PROFILES_SHORTCODE_TAG, $shortcode_ui_args );
}