<?php

namespace UDD_Corporate_Profiles;
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
			new EntriesOptions( array(
				'post_type'      => 'people_list',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC'
			))
		) );
		$form->addElement( new YesNo(
			'Mostrar fotos',
			'show_photos'
		) );
		echo $form;
		exit;
	}
}