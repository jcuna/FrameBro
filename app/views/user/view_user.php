<?php if ( isset($user) ): ?>
    <div class="user-container">
		<div class="user-profile well">
			<p class="username"><h2><?= $user->username ?></h2></p>
			<p class="name"> Name: <?= $user->fname ?></p>
			<p class="email"> Email: <?=$user->email ?></p>
		</div>
	<?php if ($user->username !== View::getUser('username') && View::hasRole(['Super Admin', 'Admin'])) : ?>
		<div class="delete-user-button">
			<?php if (!View::hasRole('Super Admin') && !in_array('Super Admin', $user->roles) || View::hasRole('Super Admin')) : ?>
				<button type="button" class="btn btn-danger delete-user" data-collect="<?= $user->username ?>">Delete this user</button>
			<?php endif; ?>
		</div>
    <?php endif; ?>

    <?php if ($user->username === View::getUser('username')) : ?>
        @partial("user.password_form")
    <?php endif; ?>
	</div>
<?php else: ?>
    <?= renderUsers($users) ?>
<?php endif; ?>


<?php
function renderUsers($users) {

	$result = '<div class="items-directory"><ul class="item-list">';
	$result .= '<li class="labels"><span>Username</span><span>First Name</span><span>Last Name</span><span>Email</span></li><hr>';
	$order = 0;
	foreach ($users as $object) {

		$order++;
		$class = ($order % 2 == 0)? "item-even" : "item-odd";
		$result .= '<li class="' . $class . '">';
		$result .= '<span><a class="item-link" href="/users/' . $object->username . '"">' . $object->username . '</a></span>';
		$result .= '<span>' . $object->fname . '</span><span>' . $object->lname . '</span><span>' . $object->email . '</span>';
		$result .= '</li>';
	}
	$result .= "</ul></div>";

	return $result;
}
?>