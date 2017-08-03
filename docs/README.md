# Instalación

El método recomendado para agregar el plugin a tu proyecto es utilizando *Composer*.
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
