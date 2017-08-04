<?php
/**
 * Menú de administración para crear "listas"
 * 	+ Cada lista tiene nombre y personas asociadas
 * 	+ La administración permite buscar personas y añadirlas a la lista
 * 	+ Al añadirla a la lista, puede editar una nota personalizada
 * 	+ Al añadirla a la lista, puede modificar el orden en que se agrega
 * 	+ Al añadirla a la lista, se ve una previsualización del perfil de la persona
 */
namespace UDD_Corporate_Profiles;

/**
 * Clase controladora de la administración de las listas de personas centralizadas
 */
class Admin {
	/**
	 * Versión de la adminstración del plugin
	 * @var string
	 */
	const VERSION = '0.1.4';

	/**
	 * Contiene el slug de la pantalla de administración
	 * @var string
	 */
	private $menu_slug = 'people_list';

	/**
	 * Inicializa el plugin y registra las acciones y filtros requeridos para su funcionamiento
	 */
	public function init() {
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('edit_form_after_title', array($this, 'insert_search_field'));
		add_action('save_post', array($this, 'save_data'));
		add_filter('enter_title_here', array($this, 'filter_title_placeholder'), 10, 2);
	}

	/**
	 * Filtrar el placholder del nombre de la lista
	 * @param  string $title Placeholder default
	 * @param  \WP_Post $post Objeto de post
	 * @return string        Placeholder custom
	 */
	public function filter_title_placeholder( $title, $post ){
		if ( $post->post_type != 'people_list' ) {
			return $title;
		}
		return 'Crea un título para identificar esta lista de personas';
	}

	/**
	 * Guardar los datos de las personas seleccionadas en la lista
	 * @param  int $postid ID del post que se está guardando
	 * @return array       Datos de personas guardadas
	 */
	public function save_data( $postid ) {
		if ( ! isset( $_POST['chosen_people']) || ! current_user_can('save_post', $postid) ) {
			return;
		}
		$people = json_decode( wp_unslash( $_POST['chosen_people'] ) );
		if ( json_last_error() ) {
			return;
		}
		update_post_meta( $postid, 'chosen_people', $people );
		return $people;
	}

	/**
	 * Insertar el campo de búsqueda y plantilla JS
	 * @param  \WP_Post $post Objeto de post
	 */
	public function insert_search_field( $post ) {
		if ( $post->post_type != $this->menu_slug )
			return;
		require __DIR__ .'/admin/search-input.php';
	}

	/**
	 * Registrar scripts y estilos de administración
	 */
	public function enqueue_scripts() {
		if ( get_current_screen()->id != $this->menu_slug )
			return;
		wp_enqueue_script(
			'jquery.selectize',
			plugins_url('/node_modules/selectize/dist/js/standalone/selectize.min.js', __FILE__),
			array('jquery'),
			'0.12.4',
			true
		);
		wp_enqueue_style(
			'jquery.selectize',
			plugins_url('/node_modules/selectize/dist/css/selectize.css', __FILE__),
			array(),
			'0.12.4',
			'screen'
		);
		wp_enqueue_script(
			'vue',
			plugins_url('/node_modules/vue/dist/vue.min.js', __FILE__),
			array(),
			'2.3.4',
			true
		);
		wp_enqueue_script(
			'sortable.js',
			plugins_url('node_modules/sortablejs/Sortable.min.js', __FILE__),
			array(),
			'1.6.0',
			true
		);
		wp_enqueue_script(
			'vue.draggable',
			plugins_url('node_modules/vuedraggable/dist/vuedraggable.js', __FILE__ ),
			array('vue'),
			'2.13.1',
			true
		);

		wp_enqueue_script('jquery-ui-dialog');
		wp_enqueue_style('jquery-ui-dialog');
		wp_enqueue_style('wp-jquery-ui-dialog');

		wp_dequeue_style( 'jquery-ui' );
		wp_enqueue_script(
			'udd.corporate-profiles.admin.js',
			plugins_url('/js/udd.corporate-profiles.admin.js', __FILE__),
			array('jquery', 'underscore', 'jquery.selectize', 'vue.draggable', ),
			static::VERSION,
			true
		);
		wp_enqueue_style(
			'udd.corporate-profiles.admin.css',
			plugins_url('/css/udd.corporate-profiles.admin.css', __FILE__),
			array(),
			static::VERSION
		);
		global $post;
		wp_localize_script( 'udd.corporate-profiles.admin.js', 'CorporateProfiles', array(
			'search_url' => $this->get_api_search_url(),
			'fetch_url'  => $this->get_api_fetch_url(),
			'people'     => $this->get_saved_people_ids( $post->ID )
		) );
	}

	private function get_saved_people_ids( $post_id ) {
		$people = get_post_meta( $post_id, 'chosen_people', true );
		return ! empty( $people ) ? $people : array();
	}

	public function get_api_baseurl() {
		return Repository::get_API_base_url();
	}

	/**
	 * Obtener la URL del API de búsqueda de personas
	 * @return string URL del API de búsqueda de personas
	 */
	public function get_api_search_url() {
		return Repository::get_API_endpoint_url('buscar');
	}

	/**
	 * Obtener la URL del API para obtener perfiles de personas
	 * @return string URL API perfiles
	 */
	public function get_api_fetch_url() {
		return Repository::get_API_endpoint_url('perfiles');
	}

	/**
	 * Obtener URL del API para un perfil de persona
	 * @param  int $id ID de la persona que se va a obtener
	 * @return string  URL del perfil vía API
	 */
	public function get_api_profile_url( $id ) {
		return trailingslashit( $this->get_api_baseurl() ) . $id;
	}
}