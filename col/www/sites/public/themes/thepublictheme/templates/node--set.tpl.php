<?php
  hide($content['comments']);
  hide($content['links']);
?>

<?php if($view_mode == 'full'):?>

<article id="node-<?php print $node->nid; ?>" class="<?php print $classes; ?> clearfix"<?php print $attributes; ?>>

  <div class="row">
    <div class="set-main col-md-8">
       <h2><?php print $title; ?></h2>
       <p><?php print $description; ?></p>
    </div>
    <div class="set-meta col-md-4">
       <table class="table table-striped">
       <tbody>
       <?php foreach($set_meta as $m): ?>
         <tr>
           <td><?php print $m['label'];?></td>
           <td><?php print $m['value'];?></td>
         </tr>
       <?php endforeach ?>
       </tbody> 
       </table>      
    </div>
  </div>
  <div class="set-images row">
    <div class="col-xs-12">
      <?php foreach ($collections as $collection): ?>
      <?php print $collection ?>
      <?php endforeach ?>
    </div>
  <div>
</article>

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
          <div class="content-main"><?php print $collection[0]; ?></div>
          <div class="content-meta"><?php print $meta[0]['value'];?></div>
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
  <div class="col-xs-12	col-sm-12 col-md-12 col-lg-123">
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

<?php endif;?>
