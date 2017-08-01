;(function($){
	var app;
	var request;
	Vue.component('selectize', {
		props: ['value'],
		template: '#selectize__template',
		mounted: function() {
			var vm = this;
			$(this.$el).selectize({
				valueField  : 'entry_id',
				labelField  : 'fn',
				searchField : 'fn',
				create      : false,
				load        : function( query, callback ) {
					if ( ! query.length ) {
						return callback();
					}
					if ( typeof request !== 'undefined' ) {
						request.abort();
					}
					request = $.ajax({
						url      : CorporateProfiles.search_url,
						data     : {
							s    : query
						},
						dataType : 'json',
						method   : 'GET',
						success  : function( value ) {
							callback( value );
						}
					});
				},
				closeAfterSelect: true,
				loadThrottle: null,
				placeholder: 'Busca el nombre de la persona que deseas agregar',
				onChange: function( value ) {
					vm.$emit('selectized', this.options[ value ] );
					this.clear();
				}
			})
		}
	});

	/**
	 * Tiempo de expiración para datos guardados en sessionStorage
	 * Le damos un tiempo breve para poder asegurar que los datos estén actualizados
	 * @type {Number}
	 */
	var expire = 1000 * 60 * 10;

	/**
	 * Verificar si los datos en caché han expirado
	 * @param  {Object}  data Objeto de datos locales
	 * @return {Boolean}      Verdadero si hay que refrescar los datos
	 */
	var isExpired = function( data ){
		var presentTime = new Date().valueOf();
		if ( ! data || ! data.timestamp ) {
			return true;
		}
		if ( presentTime - data.timestamp > expire ) {
			return true;
		}
		return false;
	};

	/**
	 * Obtener los datos para una consulta específica
	 *
	 * Siempre retorna un objeto Deferred de jQuery, que se
	 * comportan de forma similar a una Promesa.
	 * De este modo evitamos pasar callbacks para ejecutar
	 * acciones con los datos.
	 *
	 * @param  {string} key Nombre del dato que deseamos obtener
	 * @return {jQuery.Deferred} Objeto "deferred" (tipo-promesa)
	 */
	var getData = function( key ){
		var localData = JSON.parse( window.sessionStorage.getItem( key ) );
		if ( ! localData || isExpired( localData ) ) {
			// jQuery envuelve la llamada AJAX como un objeto
			// Deferred que es similar a una promesa
			return $.ajax({
				url      : CorporateProfiles.fetch_url + '/'+ key,
				dataType : 'json',
				type     : 'GET',
				cache    : false
			}).done(function( value ){
				// ya se resolvió la promesa, retorno datos
				return saveData( key, value );
			});
		} else {
			// Creo un objeto de Deferred que se resuelve
			// con los datos obtenidos desde localStorage
			var promise = new $.Deferred();
			promise.resolve( localData.content );
			return promise;
		}
	};

	/**
	 * Guardar los datos en localStorage
	 *
	 * Para controlar la expiración de los datos, siempre
	 * vamos a añadir un timestamp.
	 *
	 * @param  {string} key   Nombre del indicador
	 * @param  {object} value Datos de la API
	 * @return {object}       Datos de la API
	 */
	var saveData = function( key, value ){
		var timestamp = new Date().valueOf();
		var itemData  = JSON.stringify({
			timestamp: timestamp,
			content: value
		});
		window.sessionStorage.setItem( key, itemData );
		return value;
	};

	app = new Vue({
		el: '#people-list',
		mounted: function() {
			var peopleIds = _.pluck( this.people, 'entry_id' );
			if ( ! peopleIds.length ) {
				this.showNotice({
					'message': 'Utiliza el buscador para encontrar personas y agregarlas a tu nueva lista de personas',
					'spinner': false,
					'type': 'information'
				});
				return;
			}
			this.showNotice({
				'message': 'Actualizando información de personas desde el portal corporativo',
				'spinner': true,
				'type': 'information'
			});
			var peopleInfo = $.ajax({
				url      : CorporateProfiles.fetch_url,
				type     : 'GET',
				dataType : 'json',
				data     : {
					include: peopleIds.join(',')
				}
			});
			return peopleInfo.then(function( val ){
				_.forEach( app.$data.people, function( person ){
					var updatedPerson = _.find( val, function( item ){
						return person.entry_id == item.entry_id;
					});
					if ( updatedPerson ) {
						saveData( updatedPerson.entry_id, updatedPerson );
						person.fn = updatedPerson.entry_title;
						person.role = updatedPerson.role;
						if ( updatedPerson.featured_media ) {
							var thumb = _.find( updatedPerson._embedded.featured_media[0].sizes, function( item ){
								return item.size == 'person-thumb';
							});
							if ( thumb ) {
								person.image = thumb.bookmark;
							}
						}
					} else {
						app.$data.peopleGone++;
						person.$status = 'deleted';
					}
				});
				if ( app.$data.peopleGone ) {
					app.showNotice({
						type: 'warning',
						spinner: false,
						message: app.$data.peopleGone == 1 ? 'Se ha eliminado el perfil de 1 persona en el portal corporativo' : 'Se han eliminado los perfiles de '+ app.$data.peopleGone +' personas'
					});
				} else {
					app.showNotice({
						type: 'success',
						spinner: false,
						message: 'La información de las personas ha sido actualizada correctamente'
					});
				}
			}).fail(function(){
				app.showNotice({
					'message': 'Ha ocurrido un error al intentar actualizar los datos desde el portal corporativo',
					'type': 'error',
					'spinner': false
				});
			});
		},
		watch: {
			'notice.message': function( current, previous ) {
				$('.corporate-profiles__notice').fadeOut('fast').fadeIn();
			}
		},
		data: {
			notice: {
				isShowing: false,
				message: '',
				spinner: false,
				type: 'information'
			},
			peopleGone: 0,
			// indica si se está editando un perfil
			isEditing: false,
			// indica el índice de la persona que se está editando
			editing: null,
			// indica si se está mostrando un perfil
			isShowing: false,
			// datos de la persona que se está mostrando
			showing: null,
			// datos de las personas asociadas a esta lista
			people: CorporateProfiles.people
		},
		computed: {
			noticeClasses: function() {
				return {
					'notice-info': this.notice.type == 'information',
					'notice-error': this.notice.type == 'error',
					'notice-warning': this.notice.type == 'warning',
					'notice-success': this.notice.type == 'success'
				}
			},
			maybeShowThumbnail: function() {
				if ( ! this.showing.featured_media ) {
					return false;
				}
				var thumb = _.find( this.showing._embedded.featured_media[0].sizes, function(item){
					return item.size == 'newsletter_square';
				});
				if ( ! thumb ) {
					return false;
				}
				return thumb.bookmark;
			}
		},
		methods: {
			maybeLocalDescription: function( person ) {
				return person.local_description ? person.local_description : person.role;
			},
			addPerson: function( value ) {
				if ( value ) {
					if ( this.personExists( value.entry_id ) ) {
						this.showNotice({
							'message': value.fn + ' ya existe en esta lista',
							'type': 'warning',
							'spinner': false
						})
					} else {
						value.$order = app.$data.people.length + 1;
						app.$data.people.push( value );
						this.showNotice({
							'message': value.fn +' ha sido agregado/a a la lista',
							'type': 'information',
							'spinner': false
						});
						return true;
					}
				}
			},
			personExists: function( id ) {
				return _.find( app.$data.people, function( item ){
					return item.entry_id == id;
				});
			},
			deletePerson: function( index ) {
				app.$data.people.splice( index, 1 );
			},
			showPerson: function( id ) {
				getData( id ).then(function( data ){
					app.$data.isShowing = true;
					app.$data.showing = data;
					$('#person-profile').dialog({
						modal: true,
						classes: 'wp-dialog',
						autoOpen: true,
						width: Math.min( $(document).width() * .9, 600 ),
						height: Math.min( $(window).height() * .95, 600 ),
						buttons: [
							{
								text: 'Cerrar',
								click: function() {
									$(this).dialog('close');
								}
							}
						],
						close: function() {
							app.$data.isShowing = false;
							app.$data.showing = null;
						}
					});
				}).fail(function(){
					app.showNotice({
						'message': 'No ha sido posible obtener los datos del perfil de la persona',
						'type': 'error'
					});
				});
			},
			editPerson: function( index ) {
				if ( this.$data.isEditing ) {
					this.$data.isEditing = false;
					this.$data.editing = null;
				} else {
					this.$data.isEditing = true;
					this.$data.editing = index;
				}
			},
			showNotice: function( args ) {
				this.$data.notice = $.extend( this.$data.notice, args, { isShowing: true } );
			},
			hideNotice: function() {
				this.$data.notice = {
					isShowing: false,
					message: '',
					spinner: false,
					type: 'information'
				};
			}
		}
	});

	$('#post').on('submit', function(){
		$('#people_list__chosen_people').val( JSON.stringify( app.$data.people ) );
		return true;
	});

	Vue.directive('focus', {
		inserted: function( el ){
			el.focus();
		}
	});
})(jQuery);