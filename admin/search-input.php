<input type="hidden" name="chosen_people" id="people_list__chosen_people" value=''>

<script type="text/x-template" id="selectize__template">
	<input type="text" class="widefat">
</script>

<div id="people-list">
	<div v-cloak v-if="notice.isShowing" class="inline notice corporate-profiles__notice" v-bind:class="noticeClasses">
		<p>{{ notice.message }} <span v-if="notice.spinner" class="spinner is-active"></span></p>
	</div>
	<h3 class="corporate-profiles__label">Busca personas para agregar a la lista</h3>
	<selectize v-on:selectized="addPerson"></selectize>
	<draggable v-cloak v-model="people" class="corporate-profiles" :options="{handle:'.corporate-profile__drag'}">
		<div v-for="(person, person_index) in people" class="corporate-profiles__item" v-bind:class="{ 'corporate-profiles__item--editing': editing == person_index, 'corporate-profiles__item--deleted': person.$status && person.$status == 'deleted' }">
			<div class="corporate-profile">
				<div class="corporate-profile__card">
					<span v-if="person.image">
						<img v-bind:src="person.image" alt="" class="corporate-profile__image">
					</span>
					<span v-else>
						<span class="corporate-profile__image dashicons dashicons-admin-users"></span>
					</span>
					<div>
						<span class="corporate-profile__name">
							{{ person.fn }}
						</span><br>
						<p class="corporate-profile__role description" v-if=" ( ! person.$status || person.$status != 'deleted' ) && ! ( isEditing && editing == person_index ) " v-on:click="editPerson( person_index )">
							<span v-if="person.local_description">
								{{ person.local_description }}
							</span>
							<span v-else>
								{{ person.role }}
							</span>
							<span class="dashicons dashicons-edit corporate-profile__edit-icon"></span>
						</p>
						<p class="corporate-profile__description description" v-if="person.$status && person.$status == 'deleted'">
							El perfil de esta persona fue eliminado del portal corporativo
						</p>
						<div class="corporate-profile__role-edit" v-if="isEditing && editing == person_index">
							<input class="corporate-profile__role-input widefat" type="text" v-model="person.local_description" v-focus v-bind:placeholder="person.role"> <button class="corporate-profile__role-edit-close" v-on:click="editPerson( person_index )"><span class="dashicons dashicons-yes"></span></button>
							<small class="description">Ingresa una descripción de la persona específica para esta lista. De modo predeterminado, mostrará el cargo de la persona en UDD.</small>
						</div>
					</div>
				</div>
				<button v-on:click="showPerson( person.entry_id )" class="corporate-profile__show" type="button">Ver perfil</button>
				<button v-on:click="deletePerson( person_index )" class="corporate-profile__delete" type="button">Borrar</button>
				<span class="dashicons dashicons-menu corporate-profile__drag"></span>
				<span v-if="person.$status && person.$status == 'deleted'" v-on:click="deletePerson( person_index )" class="dashicons dashicons-no corporate-profile__remove-deleted"></span>
			</div>
		</div>
	</draggable>
	<div v-cloak id="person-profile">
		<div v-if="isShowing" class="person-profile">
			<img v-if="showing.featured_media" v-bind:src="maybeShowThumbnail" alt="" class="person-profile__image" width="62" height="62">
			<h2 class="person-profile__name">{{ showing.fn }}</h2>
			<p class="person-profile__role"><strong>{{ showing.role }}</strong></p>
			<ul v-if="showing.title">
				<li v-for="title in showing.title">{{ title }}</li>
			</ul>
			<div class="person-profile__description" v-html="showing.description.rendered"></div>
			<div v-if="showing.related_urls.length">
				<h4>Enlaces relacionados</h4>
				<ul>
					<li v-for="link in showing.related_urls">
						<a v-bind:href="link.url">{{ link.label }}</a>
					</li>
				</ul>
			</div>
			<p><strong>Perfil completo:</strong> <a v-bind:href="showing.bookmark" target="_blank">{{ showing.bookmark }}</a></p>
		</div>
	</div>
</div>