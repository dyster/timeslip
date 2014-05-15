{{ content() }}



	{{ form('class': 'form-signin', 'role': 'form') }}


		<h2 class="form-signin-heading">Please sign in</h2>


		{{ form.render('email') }}
		{{ form.render('password') }}


		<div class="checkbox">
			{{ form.render('remember') }}
			{{ form.label('remember') }}
		</div>

        {{ form.render('Sign in') }}

		{{ form.render('csrf') }}

		<hr>

		<div class="signup">
        			{{ link_to("session/signup", "Signup") }}
        </div>

		<div class="forgot">
			{{ link_to("session/forgotPassword", "Forgot my password") }}
		</div>

	</form>

