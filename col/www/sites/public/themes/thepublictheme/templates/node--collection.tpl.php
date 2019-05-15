<?php
  hide($content['comments']);
  hide($content['links']);
?>

<?php if($view_mode == 'full'):?>

<article id="node-<?php print $node->nid; ?>" class="<?php print $classes; ?> clearfix"<?php print $attributes; ?>>
  <?php print $thenav;?>

  <div class="content clearfix"<?php print $content_attributes; ?>>
    <div class="content-main col-md-7 col-lg-6">
      <?php print render($feature_image); ?>
    </div>
    <div class="content-meta col-md-5 col-lg-6">
      <h2><?php print $title; ?></h2>
      <?php print render($content);?>
      <p><b>引用網址: </b><?php print $collection_url;?></p>
      <p><b>JSON:</b><a href="/json/<?php print $node->nid; ?>"> Download </a></p>
      <br/><br/>
    </div>

  </div>
  <div class="node-comments clearfix">
    <?php if(!user_is_logged_in() && (user_access('use hybridauth'))):
  $element['#type'] = 'hybridauth_widget';
  ?>
  請用以下任一服務登入以留言（除了電子郵件帳號等認證用資訊與你留下的訊息外，我們不會保留你的個人資料。）
  <?php print drupal_render($element);?>
  <?php else:?>
    <?php print render($content['comments']);?>
  <?php endif;?>
  </div>

</article>
<?php elseif($view_mode == 'teaser'):?>
  <?php
    $index = $node->collopts['row'] + 1;
    $classes .= (($index %2) == 0)?' index-even':' index-odd';
    $attributes .= ' data-linkurl="'.$linkurl.'"';
  ?>
  <div class="col-xs-12	col-sm-6 col-md-4 col-lg-3">
    <div class="linkableblock node-teaser-wrapper"<?php print $attributes; ?>>
      <section id="node-<?php print $node->nid; ?>" class="<?php print $classes; ?> clearfix">
        <div class="content"<?php print $content_attributes; ?>>
          <div class="index"><?php print $index; ?></div>
          <div class="content-main"><?php print render($feature_image); ?></div>
          <div class="content-meta"><?php print render($content);?></div>
        </div>
      </section>
    </div>
  </div>
<?php elseif($view_mode == 'grid'):?>
  <?php
    $index = $node->collopts['row'] + 1;
    $classes .= (($index %2) == 0)?' index-even':' index-odd';
    $attributes .= ' data-linkurl="'.$linkurl.'"';
  ?>
  <div class="col-xs-12	col-sm-6 col-md-4 col-lg-3">
    <div class="linkableblock node-grid-wrapper"<?php print $attributes; ?>>
      <section id="node-<?php print $node->nid; ?>" class="<?php print $classes; ?> clearfix">
        <div class="content"<?php print $content_attributes; ?>>
          <div class="index"><?php print $index; ?></div>
          <div class="content-main"><?php print render($feature_image); ?></div>
          <div class="content-meta"><?php print render($content);?></div>
        </div>
      </section>
    </div>
  </div>
<?php elseif($view_mode == 'list'):?>
  <?php
    $index = $node->collopts['row'] + 1;
    $classes .= (($index %2) == 0)?' index-even':' index-odd';
    $attributes .= ' data-linkurl="'.$linkurl.'"';
  ?>
  <div class="col-xs-12	col-sm-12 col-md-12 col-lg-12">
    <div class="linkableblock node-list-wrapper"<?php print $attributes; ?>>
      <section id="node-<?php print $node->nid; ?>" class="<?php print $classes; ?> clearfix">
        <div class="content"<?php print $content_attributes; ?>>
          <div class="index"><?php print $index; ?></div>
          <div class="content-main"><?php print render($feature_image); ?></div>
          <div class="content-meta"><?php print render($content);?></div>
        </div>
      </section>
    </div>
  </div>

<?php elseif($view_mode == 'front'):?>
  <?php
    $index = $node->collopts['row'] + 1;
    $classes .= (($index %2) == 0)?' index-even':' index-odd';
    $classes .= ' linkableblock';
    $attributes .= ' data-linkurl="'.$linkurl.'"';
  ?>
  <div class="item col-sm-6 col-md-6 col-lg-6 ">
    <div id="node-<?php print $node->nid; ?>" class="<?php print $classes; ?>"<?php print $attributes; ?>>
        <div class="featured-image">
          <?php print render($feature_image); ?>
          <div class="identifier"><a href="/<?php print $node->nid; ?>"><?php print $node->nid; ?></a></div>
          </div>

    </div>
  </div>
<?php endif;?>
