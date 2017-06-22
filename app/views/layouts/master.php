<!DOCTYPE html>
<html>
<head>
    <title><?= isset($data->page_title)?$data->page_title:'Framebro'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"/>
    <meta name="Generator" content="Framebro">
    <meta name="Author" content="Jon Cuna">

    <!-- include scripts and css declared in the header includes -->
    @header_includes@

    <link href='https://fonts.googleapis.com/css?family=Dancing+Script' rel='stylesheet' type='text/css'>
</head>
<body>
<div class="content-wrapper">
    <nav class="navbar navbar-default">
        <div class="container-fluid">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="/"><?=App::env("APP_NAME")?></a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <?php if (View::isLoggedIn()) : ?>
                    <ul class="nav navbar-nav">
                        <?php if (View::is_user_role(['Super Admin', 'Admin'])) : ?>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Users<span class="caret"></span></a>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="/users/all">All Users</a></li>
                                <li><a href="/users/create">Create New User</a></li>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Admin<span class="caret"></span></a>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="/admin">Files</a></li>
                                <li><a href="/admin/logs">Logs</a></li>
                                <li><a href="/admin/statusReport">Status Report</a></li>
                                <li><a href="/admin/showRoutes" target="_blank">Routes</a></li>
                                <li><a href="/admin/memcachedStats" target="_blank">Memcached Stats</a></li>
                                <li><a href="/admin/info" target="_blank">System Info</a></li>
                            </ul>
                            <?php endif;?>
                        </li>
                    </ul>
                <?php endif; ?>
                <form class="navbar-form navbar-left" method="GET" action="/" role="search">
                    <div class="form-group">
                        <input type="text" name="query" class="form-control" placeholder="Search">
                    </div>
                    <?php $disable = !View::isLoggedIn() ? ' disabled="disabled"' : '' ; ?>
                    <button type="submit" class="btn btn-default"<?=$disable?>> Find</button>
                </form>
                <ul class="nav navbar-nav navbar-right">
                    <?php if (View::isLoggedIn()) : ?>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><?php echo View::getUser('username'); ?> <span class="caret"></span></a>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="/users">Change Password</a></li>
                                <li class="divider"></li>
                                <li><a href="/users/logout">Logout</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if (!View::isLoggedIn()) : ?>
                        <li><a href="<?=Router::getPath('login_path')?>">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <!-- /.navbar-collapse -->
        </div>
        <!-- /.container-fluid -->
    </nav>
    <div class="container">
        <!-- render feedback before the main content -->
        @render_feedback@

        <!-- render the main content -->
        @yield

    </div>
    <!-- end container -->
</div>
<!-- end content-wrapper -->
<div class="modal-loading"></div>
<div id="footer">
    <footer class="panel-footer">
        <div class="container">
            <p>Created by Jon Garcia</p>
            <p><a href="mailto:garciajon@me.com">garciajon@me.com</a></p>
            <p>
                <script src="//platform.linkedin.com/in.js" type="text/javascript"></script>
                <script type="IN/MemberProfile" data-id="https://www.linkedin.com/in/jonag" data-format="hover" data-text="Jon Garcia"></script>
            </p>
        </div>
    </footer>
</div>
<script src="/themes/lightbox/js/lightbox.js"></script>
</body>
</html>