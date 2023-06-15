<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();
$this->css()
	 ->css('browse.css', 'com_publications')
     ->js('browse.js', 'com_publications');

$config = Component::params('com_publications');
$fl = Request::getString('fl', '');
$activeTags= Request::getString('active-tags', '');
?>

<?php if ($this->group->published == 1) { ?>
	<ul id="page_options">
		<li>
			<a class="icon-add add btn" href="<?php echo Route::url('index.php?option=com_publications&task=draft&group=' . $this->group->get('cn')); ?>"><?php echo Lang::txt('PLG_GROUPS_PUBLICATIONS_START_A_CONTRIBUTION'); ?></a>
		</li>
	</ul>
<?php } ?>

<section class="section live-update">
    <div aria-live="polite" id="live-update-wrapper">
        <div class="browse-mobile-btn-wrapper">
            <button class="browse-mobile-btn"><span class="hz-icon icon-filter">Filter</span></button>
        </div>
        <form action="<?php echo Route::url('index.php?option=' . $this->option . '&task=browse'); ?>" method="post" id="filter-form" enctype="multipart/form-data">
            <div class="resource-container">
                <div class="filter-container">
                    <div class="text-search-options">
                        <fieldset>
                            <input type="hidden" name="action" value="browse" />
                            <input type="hidden" name="no_html" value="1" />
                            <input type="hidden" name="sortby" value="<?php echo $this->sortBy; ?>" />
                        </fieldset>
                        <fieldset>
                            <h5>Text Search:</h5>
                            <div class="search-text-wrapper">
                                <?php echo \Hubzero\Html\Builder\Input::text("search", $this->search); ?>
                                <input type="submit" class="btn" id="search-btn" value="Apply">
                            </div>
                        </fieldset>
                        <input type="submit" class="btn" id="reset-btn" value="Reset All Filters">
                    </div>
                        
                    <?php
                    // Calling filters view
                    echo $this->view('filters', 'browse')
                        ->set('fas', $this->fas)
                        ->set('filters', $this->filters)
                        ->set('facets', $this->facets)
                        ->loadTemplate();
                    ?> 
                    <input type="hidden" id="fl" name="fl" value="<?php echo $fl; ?>">
                    <input type="hidden" id="active-tags" name="active-tags" value="<?php echo $activeTags; ?>">
                </div>
                <div class="container">
                    <div class="active-filters-wrapper">
                        <h6>Applied Filters</h6>
                        <ul class="active-filters"></ul>
                    </div>
                    <div class="total-results"></div>
                    <div class="container" id="sortby">
                        <nav class="entries-filters">
                            <ul class="entries-menu order-options">
                                <li><a class="active" data-value="score" title="Relevance">Relevance</a></li>
                            </ul>
                        </nav>
                    </div>
                        <?php
                        // Calling cards view
                        echo $this->view('cards', 'browse')
                            ->set('results', $this->results)
                            ->set('pageNav', $this->pageNav)
                            ->loadTemplate();
                        ?>
                </div>
            </div>
        </form>
    </div> <!-- .live-update-wrapper -->
</section>
