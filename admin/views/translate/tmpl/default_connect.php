<h2><?php echo JText::_('CONNECT'); ?></h2>
<p>
	<?php echo JText::_('REGISTER NOTICE'); ?>
</p>

<form action="index.php" method="post" name="adminForm">
	<input type="hidden" name="option" value="com_euleo" />
	<input type="hidden" name="task" value="translate.overview" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>