<?php foreach ($this->ImagesPre as $image): ?>
<a href="<?php echo $image['href']; ?>"<?php echo $image['attributes']; ?>></a><!--testtpl-->
<?php endforeach; ?>

<table>
<tbody>
<?php foreach ($this->body as $class=>$row): ?>
<tr class="<?php echo $class; ?>">
<?php foreach ($row as $col): ?>
<?php if (!$col->addImage): ?>
  <td class="<?php echo $col->class; ?> empty">&nbsp;</td>
<?php else: ?>
  <td class="<?php echo $col->class; ?>" style="width:<?php echo $col->colWidth; ?>;">
  <div class="image_container"<?php if ($col->margin): ?> style="<?php echo $col->margin; ?>"<?php endif; ?>>
<?php if ($col->href): ?>
    <a href="<?php echo $col->href; ?>"<?php echo $col->attributes; ?> title="<?php echo $col->alt; ?>"><img src="<?php echo $col->src; ?>"<?php echo $col->imgSize; ?> alt="<?php echo $col->alt; ?>" /></a>
<?php else: ?>
    <img src="<?php echo $col->src; ?>"<?php echo $col->imgSize; ?> alt="<?php echo $col->alt; ?>" />
<?php endif; ?>
<?php if ($col->caption): ?>
    <div class="caption"><?php echo $col->caption; ?></div>
<?php endif; ?>
  </div>
  </td>
<?php endif; ?>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>


<?php foreach ($this->ImagesPost as $image): ?>
<a href="<?php echo $image['href']; ?>"<?php echo $image['attributes']; ?>></a>
<?php endforeach; ?>

<script type="text/javascript">
<?php if (!empty($GLOBALS['TL_CONFIG']['latestVersion']) && version_compare(VERSION . '.' . BUILD, 2.10, '<')) : ?>
window.addEvent('domready', function(){
   document.getElements('#cgxt_<?php echo $this->id; ?> .pagination a').addEvent('click', function(event){
          event.preventDefault();
          var page = event.target.get('href').match('page=[0-9]*')
          new Request.HTML({
                  method:'get',
                  data:'g=1&action=cte&id=<?php echo $this->id; ?>&' + page,
                  url:'ajax.php',
                  update: $('cgxt_<?php echo $this->id; ?>'),
                  onComplete: Mediabox.scanPage()
              }).send();
          return false;
      });
});
<?php else: ?>
function cgxtAddEvents()
{
    document.getElements('#cgxt_<?php echo $this->id; ?> .pagination a').addEvent('click', function(event){
          event.preventDefault();
          var page = event.target.get('href').match('page=[0-9]*')
          new Request.JSON({
                  method:'get',
                  data:'action=cte&id=<?php echo $this->id; ?>&' + page,
                  url:'ajax.php',
                  onComplete: function(responseText)
                      {
                          $('cgxt_<?php echo $this->id; ?>').set('html', responseText.content);
                          Mediabox.scanPage();
                          cgxtAddEvents();
                      }
              }).send();
          return false;
     });
}
window.addEvent('domready', function(){
   cgxtAddEvents();
});
<?php endif; ?>
</script>