<?php

namespace UDD_Corporate_Profiles;

/**
 * Mostrar listado de personas a partir de info de Huella Digital
 * @param  array $atts     Atributos del shortcode
 * @param  string $content Contenido del shortcode (gralmente vacío)
 * @return string          HTML Listado de personas
 */
function shortcode_display( $atts = array(), $content = '' ) {
	global $post;
	$atts = shortcode_atts( array(
		'lista'       => 0,
		'show_photos' => 1
	), $atts, 'perfiles_huella_digital');
	if ( ! $atts['lista'] ) {
		return '';
	}
	$people = get_people_from_list( (int) $atts['lista'] );
	if ( is_wp_error( $people ) ) {
		return '';
	}
	return apply_filters('UDD_Corporate_Profiles\shortcode_display', '', $people, $atts, $post );
}

/**
 * Obtener información de personas asociadas a una lista
 * @param  \WP_Post|int|null $post Post o ID desde el cual obtener los datos. Si es nulo se usa el valor de la global $post
 * @return array|\WP_Error         Array con objetos de personas u objeto de error de WordPress
 */
function get_people_from_list( $post = null ) {
	$post   = get_post( $post );
	$chosen = get_post_meta( $post->ID, 'chosen_people', true );
	if ( empty( $chosen ) ) {
		return false;
	}
	$people_ids = wp_list_pluck( $chosen, 'entry_id' );
	$people_ids = array_map('absint', $people_ids);
	$people     = Repository::get_by_id( $people_ids );
	if ( is_wp_error( $people ) ) {
		return $people;
	}
	// indexar por entry_id para inyectar descripciones locales
	$people = array_combine( $people_ids, $people );
	foreach ( $chosen as $local_person ) {
		$person_id = (int) $local_person->entry_id;
		if ( ! isset( $people[ $person_id ] ) ) {
			continue;
		}
		$people[ $person_id ]->local_description = isset( $local_person->local_description ) ? $local_person->local_description : '';
	}
	return array_values( $people );
}

/**
 * Obtener información de personas directamente desde API
 * @param  array|int $people_ids ID(s) de personas de quienes obtener la información
 * @return array|\WP_Error       Array con objetos de personas u objeto de error de WordPress
 */
function get_people_from_API( $people_ids ) {
	$people_ids = (array) $people_ids;
	return Repository::get_from_API( $people_ids );
}

/**
 * Obtener la imagen asociada a una persona
 * @param  object $person         [description]
 * @param  string $thumbnail_size [description]
 * @return [type]                 [description]
 */
/**
 * Obtener la imagen asociada al perfil de una persona
 * @param  object $person         Objeto de persona
 * @param  string $thumbnail_size Tamaño deseado del thumbnail
 * @param  array  $atts           Atributos adicionales de la imagen
 * @return string                 Tag HTML de imagen solicitada
 * @todo                          Generar srcset
 */
function get_person_thumbnail( $person, $thumbnail_size = '', $atts = array() ) {
	$img = get_person_image_src( $person, $thumbnail_size );
	if ( ! $img ) {
		return '';
	}
	list( $src, $width, $height ) = $img;
	$atts = wp_parse_args( $atts, array(
		'alt'   => $person->entry_title,
		'class' => "size-{$thumbnail_size}"
	) );
	$attributes = '';
	foreach ( $atts as $key => $val ) {
		$attributes .= ' '. $key .'="'. esc_attr( $val ) .'"';
	}
	return "<img src='$src' width='$width' height='$height'$attributes>";
}

/**
 * Obtener los datos de imagen del perfil de una persona
 * @param  object $person     Objeto de persona
 * @param  string $image_size Tamaño deseado de la imagen
 * @return array|false        Atributos de la imagen: URL, ancho y alto; falso si no tiene imagen o el tamaño no existe
 */
function get_person_image_src( $person, $image_size = '' ) {
	$desired_size = false;
	if ( ! isset( $person->_embedded->featured_media[0]->sizes ) ) {
		return false;
	}
	foreach ( $person->_embedded->featured_media[0]->sizes as $size ) {
		if ( $size->size == $image_size ) {
			$desired_size = $size;
			break;
		}
	}
	if ( ! $desired_size ) {
		return $desired_size;
	}
	// asegurar que los atributos se devuelven en el orden correcto
	return array(
		$desired_size->bookmark,
		(int) $desired_size->width,
		(int) $desired_size->height
	);
}

/**
 * Indica si el perfil de persona tiene una imagen asociada
 * @param  object  $person Objeto de persona
 * @return bool            Verdadero si la persona tiene imagen asociada
 */
function person_has_image( $person ) {
	return ! empty( $person->featured_media );
}