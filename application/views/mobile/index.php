<html  theme="<?php echo $activeTheme ?? ''; ?>">
    <head>
        <meta charset="utf-8">
        <meta name="author" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no, maximum-scale=2,user-scalable=1" />
        <title>w3mentors Live Demo | w3mentors</title>
        <style type="text/css">
            .page-body{height:100vh; width: 100%;}
            .mobile-preview{max-width: 428px; width: 100%; height: 100%; margin: 0 auto; position: relative; overflow: hidden;}
            .mobile-preview .iframe{position: absolute; left: 0;right: 0;top: 0; bottom: 0; margin: auto; width: 100%; height: 100%; border: none;}
            @media(min-width:576px){
                .page-body{height: calc(100vh - 42px);padding: 1rem;}
                .mobile-preview{ border-radius: 40px; border: 15px solid #000;}
            }
        </style>
        <link rel="shortcut icon" href="<?php echo MyUtility::getFavicon(); ?>" />
        <link rel="apple-touch-icon" href="<?php echo MyUtility::getFavicon(); ?>" />
    </head>    
    <body>
        <?php
        echo $this->getJsCssIncludeHtml(!CONF_DEVELOPMENT_MODE);
        echo Common::setThemeColorStyle();
        ?>
        <?php
        if (MyUtility::isDemoUrl()) {
            include(CONF_INSTALLATION_PATH . 'public/demo-header.php');
        }
        ?>
        <div class="page-body">
            <div class="mobile-preview">
                <iframe class="iframe" src="<?php echo CONF_WEBROOT_FRONT_URL; ?>" width="100%" height="100%" border="0"></iframe>
            </div>
        </div>
    </body>
</html>
