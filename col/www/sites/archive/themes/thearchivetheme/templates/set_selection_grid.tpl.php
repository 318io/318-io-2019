<div class="row">
<?php if ($type == 'add'):?>
<form action="/set/<?php print $nid; ?>/more" method="GET"> 
    <div class="col-xs-12 col-md-6">
        <span>新增藏品至特藏集 <a href="/node/<?php print $nid;?>" target="_blank"><?php print $nid ; ?></a><span>&nbsp;&nbsp;&nbsp;
        <button type="button" class="btn btn-primary" id="add_collections" data-nid="<?php print $nid; ?>">新增</button>
    </div>
    <div class="col-xs-12 col-md-6">
      <div class="input-group">
        <input type="text" class="form-control" placeholder="<?php print $qs; ?>" name="qs" id="qs"/>
        <div class="input-group-btn">
          <button class="btn btn-primary" type="submit">
            <span class="glyphicon glyphicon-search"></span>
          </button>
        </div>
      </div>
    </div>
</form>
<?php else: ?>
  <div class="col-xs-12 col-md-6">
        <span>特藏集 <a href="/node/<?php print $nid;?>" target="_blank"><?php print $nid ; ?> </a>刪除藏品<span>&nbsp;&nbsp;&nbsp;
        <button type="button" class="btn btn-primary" id="delete_collections" data-nid="<?php print $nid; ?>">刪除</button>
    </div> 
<?php endif; ?>
</div>

<div class="set-images">
      <ul id="selectable" class="row">
        <?php foreach ($collections as $collection): ?>
            <?php print $collection ?>
        <?php endforeach ?>
      </ul>
<div>
