<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php print $rdf_namespaces;?>>
<head profile="<?php print $grddl_profile; ?>">
  <meta charset="utf-8"/>
  <?php print $head; ?>
  <title><?php print $head_title; ?></title>
  <?php print $styles; ?>
  <!-- HTML5 element support for IE6-8 -->
  <!--[if lt IE 9]>
    <script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->
  <?php print $scripts; ?>
</head>
<body class="<?php print $classes; ?>">
<header id="page-header" role="banner">
  <div class="container-fluid">
    <div class="site-branding">
      <h1 class="site-title">
        <a class="site-name" href="<?php print $front_page; ?>" title="<?php print t('Home'); ?>"><?php print $site_name; ?></a>
      </h1>
    </div>
  </div>
</header>

<a id="main-content"></a>

<div id="page-content" class="main-container">
  <div class="container">
    <h1 class="title" id="page-title"><?php print $title; ?></h1>
          <?php print $content; ?>
  </div>
</div>


<footer id="footer" class="footer  text-right">
  <div class="container">
    <div class="sitemap col-sm-12 col-md-6 col-lg-6">
      <?php if($page['sitemap']):?><?php print render($page['sitemap']); ?><?php endif;?>
    </div>
    <div class="col-sm-12 col-md-6 col-lg-6">
      <?php if($page['footer']):?><?php print render($page['footer']); ?><?php endif;?>
    </div>
  </div>
</footer>

</body>
</html>
