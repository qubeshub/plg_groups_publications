<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();
$this->css()
     ->css('publications.css', 'com_publications')
     ->js('publications.js', 'com_publications');
$config = Component::params('com_publications');
// An array for storing all the links we make
$links = array();
$html = '';
?>

<?php if ($this->group->published == 1) { ?>
	<ul id="page_options">
		<li>
			<a class="icon-add add btn" href="<?php echo Route::url('index.php?option=com_publications&task=draft&group=' . $this->group->get('cn')); ?>"><?php echo Lang::txt('PLG_GROUPS_PUBLICATIONS_START_A_CONTRIBUTION'); ?></a>
		</li>
	</ul>
<?php } ?>

<section class="section">
	<form method="get" action="<?php echo Route::url('index.php?option=' . $this->option . '&cn=' . $this->group->get('cn') . '&active=publications'); ?>">

		<input type="hidden" name="area" value="<?php echo $this->escape($this->active); ?>" />

		<div class="container">
			<nav class="entries-filters">
				<ul class="entries-menu">
					<li><a<?php echo ($this->sort == 'date') ? ' class="active"' : ''; ?> href="<?php echo Route::url('index.php?option=' . $this->option . '&cn=' . $this->group->get('cn') . '&active=publications&area=' . urlencode(stripslashes($this->active)) . '&sort=date&access=' . $this->access); ?>" title="Sort by newest to oldest">&darr; <?php echo Lang::txt('PLG_GROUPS_PUBLICATIONS_SORT_BY_DATE'); ?></a></li>
					<li><a<?php echo ($this->sort == 'title') ? ' class="active"' : ''; ?> href="<?php echo Route::url('index.php?option=' . $this->option . '&cn=' . $this->group->get('cn') . '&active=publications&area=' . urlencode(stripslashes($this->active)) . '&sort=title&access=' . $this->access); ?>" title="Sort by title">&darr; <?php echo Lang::txt('PLG_GROUPS_PUBLICATIONS_SORT_BY_TITLE'); ?></a></li>
					
				</ul>
			</nav>

			<div class="container-block">
				<?php
				$html = '';
				$k = 0;
				foreach ($this->results as $category)
				{
					$amt = count($category);
					if ($amt > 0)
					{
						$html .= '<ol class="publications results">'."\n";
						foreach ($category as $row)
						{
							$k++;
							$html .= $this->view('_item') //calling _item view here
										->set('row', $row)
										->set('authorized', $this->authorized)
										->loadTemplate();
						}
						$html .= '</ol>'."\n";
					}
				}
				echo $html;
				if (!$k)
				{
					echo '<p class="warning">' . Lang::txt('PLG_GROUPS_PUBLICATIONS_NONE') . '</p>';
				}
				?>
			</div><!-- / .container-block -->
			<?php
			// $pageNav = $this->pagination(
			// 	$this->total,
			// 	$this->limitstart,
			// 	$this->limit
			// );
			// $pageNav->setAdditionalUrlParam('cn', $this->group->get('cn'));
			// $pageNav->setAdditionalUrlParam('active', 'publications');
			// $pageNav->setAdditionalUrlParam('area', urlencode(stripslashes($this->active)));
			// $pageNav->setAdditionalUrlParam('sort', $this->sort);
			// $pageNav->setAdditionalUrlParam('access', $this->access);
			// echo $pageNav->render();
			?>
			<div class="clearfix"></div>
		</div><!-- / .container -->
	</form>
</section>
