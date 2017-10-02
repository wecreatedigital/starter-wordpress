<head>
  <?php if( !empty( getenv('GOOGLE_ANALYTICS') ) ): ?>

  <script async src="https://www.googletagmanager.com/gtag/js?id=UA-107065244-1"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments)};
    gtag('js', new Date());
    gtag('config', '<?php print getenv('GOOGLE_ANALYTICS'); ?>');
  </script>
  <?php endif; ?>

  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
  <?php if( getenv('ENV') == 'local' || getenv('ENV') == 'dev' ): ?>
    <meta name="robots" content="noindex, nofollow">
  <?php endif; ?>
</head>
