<?php
$path =  request_path();
if(!user_is_logged_in() && ($path != 'user/login')){
  print '<p class="notopentopublicnotice">本網站不對外開放。</p>';
  exit;
}
?>

<header id="page-header" role="banner">
  <div class="container-fluid">
    <div class="site-branding">
      <h1 class="site-title">
        <a class="site-name" href="<?php print $front_page; ?>" title="<?php print t('Home'); ?>"><?php print $site_name; ?></a>
      </h1>
    </div>
  </div>
</header>

<?php if (!empty($page['help']) || !empty($messages)): ?>
<div id="message">
  <?php print $messages; ?>
  <?php print render($page['help']); ?>
</div>
<?php endif; ?>

<a id="main-content"></a>
