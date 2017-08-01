<?php

namespace UDD_Corporate_Profiles;
use WP_Error;

/**
 * Controla la comunicación con el API de Huella Digital
 */
class Repository {
	/**
	 * URL predeterminada del servicio Huella Digital
	 * @var string
	 */
	const DEFAULT_API_BASE_URL = 'http://www.udd.cl/wp-json/huella-digital/v1/';

	/**
	 * Obtener los datos de 1 o más personas a partir de su ID
	 * @param  int|array $ids  ID(s) de las personas consultadas
	 * @return array|WP_Error  Array con objetos de personas u objeto de error de WordPress
	 */
	public static function get_by_id( $ids ) {
		$ids      = (array) $ids;
		$cached   = array();
		$uncached = array();
		// chequear contra caché local
		foreach ( $ids as $id ) {
			$person_is_cached = get_transient("perfil_huella_{$id}");
			if ( $person_is_cached ) {
				$cached[] = $person_is_cached;
			} else {
				$uncached[] = $id;
			}
		}
		// si están todos en caché, retornar
		if ( empty( $uncached ) ) {
			return $cached;
		}

		// así sólo necesitamos obtener los datos de los que no estén en transient
		$remote = static::get_from_API( $uncached );
		if ( is_wp_error( $remote ) ) {
			// loguear
			static::log_error('Error al consultar el API de Huella Digital', $remote );
			return $remote;
		}

		$response = wp_remote_retrieve_body( $remote );
		$people   = json_decode( $response );
		if ( json_last_error() ) {
			// loguear
			$error_message = function_exists('json_last_error_msg') ? json_last_error_msg() : json_last_error();
			static::log_error('Error en la respuesta del API de Huella Digital', array(
				'message' => $error_message,
				'data'    => $response
			));
			return new WP_Error('api_response_invalid_json', 'Error al intentar decodificar la respuesta JSON', $people);
		}
		foreach ( $people as $person ) {
			// set_transient
			set_transient("perfil_huella_{$person->entry_id}", $person, static::get_transient_lifetime() );
			// agregar a $cached
			$cached[] = $person;
		}

		// ordenar todos los $cached según el orden indicado al llamar el método
		usort( $cached, function( $a, $b ) use ( $ids ) {
			$order_a = array_search( $a->entry_id, $ids );
			$order_b = array_search( $b->entry_id, $ids );
			// en php 7 esto se puede hacer con el cohetito <=>
			if ( $order_a > $order_b ) {
				return 1;
			}
			if ( $order_a < $order_b ) {
				return -1;
			}
			return 0;
		} );

		// retornar
		return $cached;
	}

	/**
	 * Obtener el tiempo que se deben mantener los datos en caché, en segundos (mínimo 1 hora)
	 * @return int Cantidad de segundos que se guardan los datos en caché
	 */
	private static function get_transient_lifetime( ) {
		$default = HOUR_IN_SECONDS * 4;
		$lifetime = apply_filters('UDD_Corporate_Profiles\Repository\transient_lifetime', $default );
		if ( is_int( $lifetime ) && $lifetime > HOUR_IN_SECONDS ) {
			return (int) $lifetime;
		}
		return $default;
	}

	/**
	 * Intentar loguear información de un error
	 * @param  string $message Mensaje descriptivo del error
	 * @param  array $context  Información de contexto
	 * @return void
	 */
	private static function log_error( $message, $context ){
		if ( function_exists('SimpleLogger') ) {
			SimpleLogger()->error( $message, $context );
		} else {
			error_log( str_repeat('-', min( strlen( $message), 80 ) ) );
			error_log( $message );
			error_log( print_r( $context, true ) );
			error_log( str_repeat('-', min( strlen( $message), 80 ) ) );
		}
	}

	/**
	 * Obtener los datos de 1 o más personas directamente desde el API
	 * @param  int|array $ids ID(s) de las personas consultadas
	 * @return array|WP_Error Datos de la respuesta u objeto de error de WordPress
	 */
	public static function get_from_API( $ids ) {
		$ids = (array) $ids;
		$request_url = add_query_arg(
			'include',
			implode(',', $ids),
			static::get_API_endpoint_url('perfiles')
		);
		return wp_remote_get( $request_url );
	}

	/**
	 * Obtener la URL base del API de Huella Digital
	 * @return string URL base del API de Huella Digital
	 */
	public static function get_API_base_url() {
		if ( ! defined('CORPORATE_PROFILES_API_BASE_URL') ) {
			return static::DEFAULT_API_BASE_URL;
		} else {
			return trailingslashit( CORPORATE_PROFILES_API_BASE_URL );
		}
	}

	/**
	 * Obtener la URL de un endpoint de Huella Digital
	 * @param  string $endpoint Endpoint solicitado
	 * @return string           URL del endpoint
	 */
	public static function get_API_endpoint_url( $endpoint = '' ) {
		if ( ! $endpoint ) {
			return static::get_API_base_url();
		}
		return esc_url_raw( untrailingslashit( trailingslashit( static::get_API_base_url() ) . $endpoint ) );
	}
}