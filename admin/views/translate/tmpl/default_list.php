<?php
/**
 * Joom!Fish extension for Euleo® Soap-API
 * Copyright (C) 2010 Euleo GmbH
 *
 * All rights reserved. The Joom!Fish project is a set of extentions for
 * the content management system Joomla!. It enables Joomla!
 * to manage multi lingual sites especially in all dynamic information
 * which are stored in the database.
 * 
 * The Euleo® extension transfers translateable content to Euleo® where
 * it will be translated and proofread by professional, certified translators.
 * After translation it will be transferred back to your CMS automatically.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307,USA.
 *
 * The "GNU General Public License" (GPL) is available at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * -----------------------------------------------------------------------------
 * $Id: default_list.php 1344 2009-06-18 11:50:09Z akede $
 * @package joomfish
 * @subpackage Views
 *
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

$user =& JFactory::getUser();
$db =& JFactory::getDBO();

$model = &$this->getModel();

$filterOptions = '<table><tr><td width="100%"></td>';
$filterOptions .= '<td  nowrap="nowrap" align="center">' .JText::_('Languages'). ':<br/>' .$this->langlist. '</td>';
$filterOptions .= '<td  nowrap="nowrap" align="center">' .JText::_('Content elements'). ':<br/>' .$this->clist. '</td>';
$filterOptions .= '</tr></table>';

if (isset($this->filterlist) && count($this->filterlist)>0){
	$filterOptions .= '<table><tr><td width="100%"></td>';
	foreach ($this->filterlist as $fl){
		if (is_array($fl))		$filterOptions .= "<td nowrap='nowrap' align='center'>".$fl["title"].":<br/>".$fl["html"]."</td>";
	}
	$filterOptions .= '</tr></table>';
}

$cart =& $this->cart;
$languageCode = & $this->languageCode;
$defaultSrcLang = $model->getDefaultSrcLang();

	if (false && $this->unsupported) {
		?>
			<div class="error">
				<h1><?php echo JText::_('ERROR'); ?></h1>
				<p><?php echo JText::_('LANGUAGE COMBINATIONS UNSUPPORTED'); ?></p>
				<ul class="unsupported">
					<?php 
						foreach ($this->unsupported as $srcLang => $language) {
							$srcLangName = $model->getNameByShortCode($srcLang);
							foreach ($language as $dstLang => $foo) {
								$dstLangName = $model->getNameByShortCode($dstLang);
								?>
									<li>
										<?php echo $srcLangName; ?>
										&gt;
										<?php echo $dstLangName; ?>
									</li>
								<?php
							}
						}
					?>
				</ul>
			</div>
		<?php
	}
	
	if ($cart['allLanguages']) {
		?>
			<h1><?php echo JText::_('CART'); ?></h1>
			<table class="shoppingcart" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<th><?php echo JText::_('LANGUAGE COMBINATIONS'); ?></th>
					<td>
						<ul class="allLanguages">
							<?php 
								foreach ($cart['allLanguages'] as $srcLang => $language) {
									foreach ($language as $dstLang => $lang) {
										?>
											<li>
												<?php echo $lang['count']; ?> <?php echo JText::_('TEXTS'); ?> <?php echo $lang['title'] . ': <b>' . $lang['price'] . '</b>'; ?>
											</li>
										<?php
									}
								}
							?>
						</ul>
					</td>
				</tr>
				<?php 
					if ($cart['basePrices']) {
						?>
							<tr>
								<th><?php echo JText::_('BASE PRICES'); ?></th>
								<td>
									<ul class="basePrices">
										<?php 
											foreach ($cart['basePrices'] as $srcLang => $language) {
												foreach ($language as $dstLang => $lang) {
													?>
														<li>
															<?php echo $lang['title']; ?>: <b><?php echo $lang['price']; ?></b>
														</li>
													<?php 
												}
											}
										?>
									</ul>
								</td>
							</tr>
						<?php
					}
					
					if ($cart['deliveryTime']) {
						?>
							<tr>
								<th><?php echo JText::_('DELIVERYDATE'); ?></th>
								<td><span><?php echo JHTML::_('date', $cart['deliveryTime'], JText::_('DATE_FORMAT_LC2') ); ?></span></td>
							</tr>
						<?php
					}
					
					if ($cart['totalPrice']) {
						?>
							<tr>
								<th><?php echo JText::_('NETPRICE'); ?>:</th>
								<td>
									<div class="cart_price">
										<div class="cart_priceBox">
											<span id="price"><?php echo $cart['totalPrice']; ?></span>
										</div>
									</div>
								</td>
							</tr>
						<?php
					}
					
				?>
					<tr>
						<th colspan="2">
							<div class="showCart">
								<a href="index.php?option=com_euleo&amp;task=translate.showCart" onclick="window.open(this.href);return false;">
									<img src="components/com_euleo/assets/images/icon-32-showCart.png" alt="<?php echo JText::_('SHOW CART'); ?>" />
									<span><?php echo JText::_('SHOW CART'); ?></span>
								</a>
							</div>
						</th>
					</tr>
			</table>
		<?php
	} else {
		?>
			<h2><?php echo JText::_('CART EMPTY'); ?></h2>
		<?php
	}
?>
<hr/>
<form action="index.php" method="post" name="adminForm">
  <?php echo $filterOptions; ?>
  <table class="adminlist" cellspacing="1">
  <thead>
    <tr>
      <th width="20"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count($this->rows); ?>);" /></th>
      <th class="title" align="left"  nowrap="nowrap"><?php echo JText::_('TITLE');?></th>
      <th class="cart" width="5%" align="left"  nowrap="nowrap"><?php echo JText::_('CART');?></th>
      <th width="130" align="left" nowrap="nowrap"><?php echo JText::_('SOURCE LANGUAGE');?></th>
      <th width="130" align="left" nowrap="nowrap"><?php echo JText::_('DESTINATION LANGUAGE');?></th>
      <th width="20%" align="left" nowrap="nowrap"><?php echo JText::_('TITLE_PRICE');?></th>
      <th width="60" nowrap="nowrap" align="center"><?php echo JText::_('TITLE_STATE');?></th>
      <th width="60" align="center" nowrap="nowrap"><?php echo JText::_('TITLE_PUBLISHED');?></th>
    </tr>
    </thead>
    <tfoot>
        <tr>
    	  <td align="center" colspan="8">
			<?php echo $this->pageNav->getListFooter(); ?>
		  </td>
		</tr>
    </tfoot>
    
    <tbody>
    <?php
    $k=0;
    $i=0;
	foreach ($this->rows as $row ) {
		$table =& $row->getTable();
		
		$srcLang = '';
		
		if (is_object($row->attribs)) {
			$language = $row->attribs->get('language');
			if ($language) {
				$srcLang = $model->getShortCodeByCode($language);
				$srcLangLong = '<b>' . $model->getNameByCode($language) . '</b>';
			}
		}
		
		if (!$srcLang) {
			$srcLang = $defaultSrcLang;
			$srcLangLong = '<b>' . $model->getNameByShortCode($defaultSrcLang) . '</b>';
			
			$srcLangLong = sprintf(JText::_('NOT SPECIFIED'), $srcLangLong);
		}
		
				?>
    <tr class="<?php echo "row$k"; ?>">
      <td width="20">
        <?php		if ($row->checked_out && $row->checked_out != $user->id) { ?>
        &nbsp;
        <?php		} else { ?>
        <input type="checkbox" id="cb<?php echo $i;?>" name="cid[]" value="<?php echo $row->translation_id."|".$row->id."|".$row->language_id; ?>" onclick="isChecked(this.checked);" />
        <?php		} ?>
      </td>
      <td>
      	<?php echo $row->title; ?>
	  </td>
	  <td align="center">
	  	<?php 
	  		$code = $table->Name . '_' . $row->id;
	  		if (!$row->checked_out || $row->checked_out == $user->id) {
	  			if (!isset($this->unsupported[$srcLang][$languageCode]) || !$this->unsupported[$srcLang][$languageCode]) {
			  		if (isset($cart['alreadyOrdered'][$code][$languageCode])) {
			  			?>
			  				<img src="components/com_euleo/assets/images/icon-24-pending.png" alt="<?php echo JText::_('TRANSLATION PENDING'); ?>" title="<?php echo JText::_('TRANSLATION PENDING'); ?>" />
			  			<?php
			  		} else {
				  		if (isset($cart['request2languages'][$code]) && isset($cart['request2languages'][$code][$languageCode])) {
				  			?>
				  				<a href="#edit" onclick="hideMainMenu(); return listItemTask('cb<?php echo $i;?>','translate.remove');"><img src="components/com_euleo/assets/images/icon-24-remove.png" alt="<?php echo JText::_('REMOVE FROM CART'); ?>" title="<?php echo JText::_('REMOVE FROM CART'); ?>" /></a>
				  			<?php 
				  		} else {
				  			?>
				  				<a href="#edit" onclick="hideMainMenu(); return listItemTask('cb<?php echo $i;?>','translate.euleo');"><img src="components/com_euleo/assets/images/icon-24-addToCart.png" alt="<?php echo JText::_('ADD TO CART'); ?>" title="<?php echo JText::_('ADD TO CART'); ?>" /></a>
				  			<?php 
				  		}
			  		}
	  			} else {
	  				?>
		  				<img src="components/com_euleo/assets/images/icon-24-unsupported.png" alt="<?php echo JText::_('TRANSLATION UNSUPPORTED'); ?>" title="<?php echo JText::_('TRANSLATION UNSUPPORTED'); ?>" />
		  			<?php
	  			}
	  		}
	  	?>
	  </td>
	  <td>
	  	<?php 
	  		echo $srcLangLong;
	  		if (isset($this->identifiedLanguages[$code]) && $this->identifiedLanguages[$code]['code'] != $srcLang) {
	  			?>
	  				<div class="warning">
	  					<?php
	  						$warning = sprintf(JText::_('LANGUAGE WARNING'), $this->identifiedLanguages[$code]['long']);
	  						echo $warning;
	  					?>
	  				</div>
	  			<?php 
	  		}
	  	?>
	  </td>
      <td nowrap><?php echo $row->language ? $row->language : JText::_('NOTRANSLATIONYET') ; ?></td>
      <td>
      	<?php
      		if ($row->checked_out && $row->checked_out != $user->id) {
      			echo JText::_('CHECKED OUT');
      		} else {
	      		if (isset($cart['alreadyOrdered'][$code][$languageCode])) {
	      			echo JText::_('TRANSLATION PENDING');
	      		} else {
	      			echo $cart['request2languages'][$code][$languageCode];
	      		}
      		}
      	?>
      </td>
				<?php
				switch( $row->state ) {
					case 1:
						$img = 'status_g.png';
						break;
					case 0:
						$img = 'status_y.png';
						break;
					case -1:
					default:
						$img = 'status_r.png';
						break;
				}
				?>
      <td align="center"><img src="components/com_joomfish/assets/images/<?php echo $img;?>" width="12" height="12" border="0" alt="" /></td>
				<?php
				if (isset($row->published) && $row->published) {
					$img = 'publish_g.png';
				} else {
					$img = 'publish_x.png';
				}

				$href = '<img src="images/' .$img. '" width="12" height="12" border="0" alt="" />';
				?>
      <td align="center"><?php echo $href;?></td>
	</tr>
		<?php
		$k = 1 - $k;
		$i++;
	}?>
	</tbody>
</table>
<table cellspacing="0" cellpadding="4" border="0" align="center">
  <tr align="center">
    <td> <img src="components/com_joomfish/assets/images/status_g.png" width="12" height="12" border=0 alt="<?php echo JText::_('STATE_OK');?>" />
    </td>
    <td> <?php echo JText::_('TRANSLATION_UPTODATE');?> |</td>
    <td> <img src="components/com_joomfish/assets/images/status_y.png" width="12" height="12" border=0 alt="<?php echo JText::_('STATE_CHANGED');?>" />
    </td>
    <td> <?php echo JText::_('TRANSLATION_INCOMPLETE');?> |</td>
    <td> <img src="components/com_joomfish/assets/images/status_r.png" width="12" height="12" border=0 alt="<?php echo JText::_('STATE_NOTEXISTING');?>" />
    </td>
    <td> <?php echo JText::_('TRANSLATION_NOT_EXISTING');?></td>
  </tr>
  <tr align="center">
    <td> <img src="images/publish_g.png" width="12" height="12" border=0 alt="<?php echo JText::_('Translation visible');?>" />
    </td>
    <td> <?php echo JText::_('TRANSLATION_PUBLISHED');?>  |</td>
    <td> <img src="images/publish_x.png" width="12" height="12" border=0 alt="<?php echo JText::_('Finished');?>" />
    </td>
    <td> <?php echo JText::_('TRANSLATION_NOT_PUBLISHED');?></td>
    <td> &nbsp;
    </td>
  </tr>
</table>

	<input type="hidden" name="option" value="com_euleo" />
	<input type="hidden" name="task" value="translate.overview" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
