<?php

namespace UDD_Corporate_Profiles;
use WP_Query;
use EntriesOptions;
use GutenPress\Forms\MetaboxForm;
use GutenPress\Forms\Element\YesNo;
use GutenPress\Forms\Element\Select;
use GutenPress\Model\Shortcode;

/**
 * Definir interfaz para el shortcode
 */
class Legacy_Shortcode extends Shortcode {
	/**
	 * @inheritDoc
	 */
	public function setTag(){
		$this->tag = 'perfiles_huella_digital';
	}

	/**
	 * @inheritDoc
	 */
	public function setFriendlyName(){
		$this->friendly_name = 'Perfiles Huella digital';
	}

	/**
	 * @inheritDoc
	 */
	public function setDescription(){
		$this->description = 'Permite insertar una lista de personas Huella Digital UDD';
	}

	/**
	 * @inheritDoc
	 */
	public function display( $atts, $content ){
		return shortcode_display( $atts, $content );
	}

	/**
	 * @inheritDoc
	 */
	public function configForm(){
		$form = new MetaboxForm('huella-digital-shotcode-form');
		$form->addElement( new Select(
			'Seleccionar Lista de Personas',
			'lista',
			$this->get_list_options()
		) );
		$form->addElement( new YesNo(
			'Mostrar fotos',
			'show_photos'
		) );
		echo $form;
		exit;
	}

	/**
	 * Obtener opciones de listas de personas Huella
	 * @return array Array con clave ID => valor título de la lista
	 */
	private function get_list_options() {
		$lists = new WP_Query( array(
			'post_type'      => 'people_list',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC'
		) );
		$options = array();
		if ( ! $lists->have_posts() ) {
			$options[''] = 'No hay listas disponibles';
		}
		foreach ( $lists->posts as $list ) {
			$options[ $list->ID ] = $list->post_title;
		}
		$options = apply_filters('UDD_Corporate_Profiles\Legacy_Shortcode\list_options', $options, $lists );
		return $options;
	}
}