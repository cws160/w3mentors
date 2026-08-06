<!doctype html>
<html dir="<?php echo $siteLanguage['language_direction']; ?>">

<head>
    <meta charset="utf-8">
    <style>
        html,
        body {
            line-height: 1.15;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            -ms-overflow-style: scrollbar;
            -webkit-tap-highlight-color: transparent;
            font-size: 14px;
            font-family: var(--font-family-base);
            color: var(--color-black);
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="/public/index.php?url=js-css/css&f=css/common-<?php echo $siteLanguage['language_direction']; ?>.css" />
    <base target="_parent">
</head>

<body>
    <div class="editor-content">
        <?php echo html_entity_decode($data); ?>
    </div>
</body>

</html>