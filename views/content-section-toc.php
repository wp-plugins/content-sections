<ul id="content-sections-toc">
<?php foreach($toc as $slug => $item): ?>
	<li><a href="#<?php echo $item['slug']; ?>"><?php echo $item['name']; ?></a></li>
<?php endforeach; ?>
</ul>