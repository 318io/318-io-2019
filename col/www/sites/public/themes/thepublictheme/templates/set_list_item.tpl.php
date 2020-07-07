<div class="col-xs-12 set-list-item node-id-<?php print $nid; ?>">
  <div class="row">
    <div class="col-sm-3 hidden-xs set-list-item-picture">
      <?php print render($picture); ?>
    </div>
    <div class="col-xs-12 hidden-sm hidden-md hidden-lg set-list-item-picture">
      <?php print render($picture); ?>
    </div>
    <div class="col-xs-12 col-sm-9 set-list-item-content">
      <h3><?php print $title; ?></h3>
      <p>
        <?php print $content; ?><a href="/node/<?php print $nid; ?>">[閱讀更多]</a>
      </p>
    </div>
  </div>
</div>
