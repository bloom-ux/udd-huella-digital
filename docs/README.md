# Introducción

Este plugin permite integrar la funcionalidad del API *UDD Huella Digital* en tu proyecto de WordPress.

Huella Digital es una API REST que disponibiliza la información de los perfiles de personas asociadas a la Universidad del Desarrollo.

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

* El valor de la propiedad `require.bloom-ux/huella-digital` especifica la restricción de versiones para el plugin. En el ejemplo, indica que se instalará cualquier versión mayor a `0.1` y menor que `2.0.0`. Las versiones se especifican con [versionamiento semántico](http://semver.org/)
* En `extra.installer-paths` debes indicar la ruta a la carpeta donde se instalan los plugins de WordPress en tu proyecto. En este ejemplo, corresponde a `htdocs/wp-content/plugins`. `{$name}` es variable y corresponde al nombre del plugin

Para mantener actualizado el plugin basta con ejecutar `composer update`.

# Integración con tu proyecto

El plugin provee las funcionalidades básicas para poder crear y administrar listas de personas con información proveniente del portal corporativo UDD.

La visualización de los perfiles es completamente personalizable y debería ser responsabilidad del tema de WordPress utilizado en el sitio. Para esto, debes crear una función o método que se enganche en el filtro `UDD_Corporate_Profiles\shortcode_display`

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