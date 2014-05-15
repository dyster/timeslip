{{ content() }}



	{{ form('class': 'form-search') }}


			<h2>Sign Up</h2>


		{{ form.label('name') }}</td>

					{{ form.render('name') }}
					{{ form.messages('name') }}
				{{ form.label('email') }}

					{{ form.render('email') }}
					{{ form.messages('email') }}
				{{ form.label('password') }}
					{{ form.render('password') }}
					{{ form.messages('password') }}
				{{ form.label('confirmPassword') }}

					{{ form.render('confirmPassword') }}
					{{ form.messages('confirmPassword') }}
                    <div class="checkbox">
					    {{ form.render('terms') }} {{ form.label('terms') }}
					</div>
					{{ form.messages('terms') }}
				{{ form.render('Sign Up') }}

		{{ form.render('csrf') }}
		{{ form.messages('csrf') }}

	</form>