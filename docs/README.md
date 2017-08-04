# Introducción

Este plugin permite integrar la funcionalidad del API **UDD Huella Digital** en tu proyecto de WordPress.

Huella Digital es una API REST que disponibiliza la información de los perfiles de personas asociadas a la Universidad del Desarrollo.

El plugin agrega una administración de **listas de personas** en las que un editor puede añadir los perfiles de personas desde el portal corporativo, ordenarlos, y editar su cargo o rol de forma local, de modo de poder mostrar una descripción específica para la lista donde se inserta la persona.

Estas listas luego se pueden insertar en una página o post mediante un **shortcode**.

# Instalación

El método recomendado para agregar el plugin a tu proyecto es utilizando [Composer](https://getcomposer.org/).
Para ello debes añadir la siguiente configuración a tu archivo composer.json

```json
{
	"repositories": [
		{
			"type": "vcs",
			"url": "https://github.com/bloom-ux/udd-huella-digital.git"			
		}
	],
	"require": {
		"bloom-ux/udd-huella-digital": ">=0.1 <2.0.0"
	},
	"extra": {
		"installer-paths": {
			"htdocs/wp-content/plugins/{$name}/": ["type:wordpress-plugin"]
		}
	}
}
```

* El valor de la propiedad `require.bloom-ux/huella-digital` especifica la restricción de versiones para el plugin. En el ejemplo, indica que se instalará cualquier versión mayor a `0.1` y menor que `2.0.0`. Las versiones del plugin se especifican con [versionamiento semántico](http://semver.org/)
* En `extra.installer-paths` debes indicar la ruta a la carpeta donde se instalan los plugins de WordPress en tu proyecto. En este ejemplo, corresponde a `htdocs/wp-content/plugins`. `{$name}` es variable y corresponde al nombre del plugin. Si tu proyecto se ubica dentro de una carpeta `web`, debes indicar algo como `web/wp-content/plugins/{$name}`

Para instalar el plugin debes ejecutar `compose install`.

Para mantener actualizado el plugin basta con ejecutar `composer update`.

# Integración con tu proyecto

El plugin provee las funcionalidades básicas para poder crear y administrar listas de personas con información proveniente del portal corporativo UDD, pero la integración con tu proyecto es muy flexible gracias a la posibilidad de intervenir el funcionamiento del plugin aplicando filtros a sus opciones más relevantes.

## Visualización de los perfiles

La visualización de los perfiles es completamente personalizable y **es responsbilidad del tema de WordPress utilizado en el sitio**.

Para definir la visualización de la lista de perfiles, debes crear una función o método que se enganche en el filtro `UDD_Corporate_Profiles\shortcode_display`, por ejemplo:

```php
add_filter('UDD_Corporate_Profiles\shortcode_display', 'filtrar_listado_perfiles_huella', 10, 4);

/**
 * Filtrar el output de listados de personas huella
 * @param  string        $out    Output HTML
 * @param  array         $people Array con objetos de personas
 * @param  array         $atts   Atributos del Shortcode
 * @param  \WP_Post|null $post   Post donde se llama el shortcode
 * @return string                Output HTML
 */
function filtrar_listado_perfiles_huella( $out = '', $people = array(), $atts = array(), $post = null ) {
	foreach ( $people as $person ) {
		$out .= $person->entry_title;
	}
	return $out;
}
```

## Opciones permitidas del shortcode

De manera predeterminada, el shortcode sólo recibe el atributo `lista`, que debe tener un valor numérico que indica el ID de la entrada donde se ha guardado la información de los perfiles seleccionados.

Puedes disponibilizar más atributos para el shortcode con el filtro `UDD_Corporate_Profiles\shortcode_display\defaults`. Por ejemplo, podrías hacer opcional la visualización de las fotografías de cada persona:

```php
add_filter('UDD_Corporate_Profiles\shortcode_display\defaults', 'filtrar_opciones_shortcode_perfiles_huella');

function filtrar_opciones_shortcode_perfiles_huella( $defaults ) {
	$defaults['mostrar_fotos'] = 1;
	return $defaults;
}
```

Luego, un usuario puede determinar si el listado de personas debe mostrar las fotos con `[perfiles_huella_digital lista="1313" mostrar_fotos="1"]` -- la función que genera el output debe verificar el valor del atributo y de acuerdo a eso mostrar o no las fotos.

## Integración con Shortcode UI

[Shortcode UI](https://wordpress.org/plugins/shortcode-ui/) es un plugin para WordPress que permite definir una interfaz fácilmente administrable para insertar y previsualizar shortcodes en el editor de texto de WordPress.

El plugin *Perfiles Huella Digital UDD* se integra con *Shortcode UI* y es posible modificar las opciones disponibles en la administración utilizando el filtro `UDD_Corporate_Profiles\register_shortcode_ui\shortcode_ui_args`.

Por ejemplo, para añadir la opción de mostrar fotos:

```php
add_filter('UDD_Corporate_Profiles\register_shortcode_ui\shortcode_ui_args', 'filtrar_opciones_shortcode_ui_huella');

function filtrar_opciones_shortcode_ui_huella( $args ) {
	$args['attrs'][] = array(
		'attr'  => 'mostrar_fotos',
		'type'  => 'checkbox',
		'label' => '¿Mostrar fotos?'
	);
	return $args;
}
```

Al añadir opciones administrables, debes recordar agregarlas también como opciones permitidas del shortcode según lo indicado en la sección anterior.

# Opciones avanzadas

## Definir una URL distinta para la API de Huella Digital

Puedes modificar la URL de la API de Huella definiendo la constante `CORPORATE_PROFILES_API_BASE_URL`.

Por ejemplo, al realizar pruebas locales de desarrollo, puedes definir esta constante en el archivo `wp-config.php`:

```php
define('CORPORATE_PROFILES_API_BASE_URL', 'http://localhost/huella/fixtures/');
```

## Filtrar el tiempo de caché de datos

El plugin almacena en caché local los datos obtenidos desde la API de Huella Digital usando la [API de "Transients" de WordPress](https://www.yukei.net/2010/11/usando-la-api-de-transients-en-wordpress/)

Puedes modificar el tiempo de expiración de los datos en caché utilizando el filtro `UDD_Corporate_Profiles\Repository\transient_lifetime` para indicar la cantidad de segundos que se guardarán los datos en caché; por ejemplo:

```php
add_filter('UDD_Corporate_Profiles\Repository\transient_lifetime', function( $expire ){
	return 	8 * HOUR_IN_SECONDS;
});
```

El valor predeterminado del tiempo de expiración es de 4 horas. El valor mínimo es de 1 hora.

## Filtrar parámetros de petición HTTP a la API

Puede modificar los parámetros de las peticiones HTTP a la API de Huella con el filtro `UDD_Corporate_Profiles\Repository\get_from_API_args`;

Por ejemplo, si necesitas añadir parámetros de autenticación HTTP a la petición:

```php
add_filter('UDD_Corporate_Profiles\Repository\get_from_API_args', function( $args ){
	$args['headers']['Authorization'] = 'Basic '. base64_encode('usuario:password');
	return $args;
});
```

## Filtrar parámetros de registro del tipo personalizado de contenidos

El plugin registra un tipo personalizado de contenidos para generar la administración de listas de personas.

Puedes modificar los parámetros de registro del tipo personalizado el filtro `udd_profiles_register_post_type\post_type_args`.

Por ejemplo, para modificar el ícono de acceso a la administración de listas:

```php
add_filter('udd_profiles_register_post_type\post_type_args', function( $args ){
	$args['menu_icon'] = 'dashicons-id-alt';
	return $args;
});
```