<div class="change-pw col-md-4">
	<span>Change your password</span>
	<form name="change-pw" action="/users" method="post" accept-charset="utf-8">
	  <div class="form-group">
	    <label for="current_pw">Enter your current password*</label>
	    <input type="password" class="form-control" name="current_password" id="current_pw" placeholder="Enter your current password">
	  </div>
	  <div class="form-group">
	    <label for="user_password">New password*</label>
	    <input type="password" class="form-control" name="new_password" id="new_password" placeholder="Password">
	  </div>
  	  <div class="form-group">
	    <label for="user_password_repeat">Confirm new password*</label>
	    <input type="password" class="form-control" name="new_password_repeat" id="new_password_repeat" placeholder="Confirm new password">
	  </div>
	  <button type="submit" class="btn btn-default">Submit</button>
	</form>
</div>