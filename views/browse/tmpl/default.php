<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();
$this->css()
     ->css('intro.css', 'com_publications')
	 ->css('browse.css', 'com_publications')
     ->js('browse.js', 'com_publications');

$config = Component::params('com_publications');
$fl = Request::getString('fl', '');
$activeTags= Request::getString('active-tags', '');

$relevance_classes = array();
if ($this->sortBy == 'score') { $relevance_classes[] = 'active'; }
if (!$this->search) { $relevance_classes[] = 'disabled'; }
$relevance_classes = implode($relevance_classes, ' ');
?>

<section class="group-publications-header">
    <?php if ($this->group->published == 1) { ?>
        <ul id="page_options">
            <li>
                <a id="submit-resource" class="icon-add add btn" href="<?php echo Route::url('index.php?option=com_publications&task=submit&action=choose&gid=' . $this->group->get('gidNumber') . '&base=' . $this->mtype); ?>"><?php echo Lang::txt('PLG_GROUPS_PUBLICATIONS_START_A_CONTRIBUTION', $this->mtype_alias); ?></a>
            </li>
        </ul>
    <?php } ?>
    <div class="additional-resources-search">
        <h5>Want to find additional resources? <a href="/publications/browse">Browse resources on QUBES</a>! <br>
        QUBES hosts not only the resources shown here, but many more created by the broader QUBES community.</h5>
    </div>
</section>

<section class="section live-update">
    <div aria-live="polite" id="live-update-wrapper">
        <div class="browse-mobile-btn-wrapper">
            <button class="browse-mobile-btn"><span class="hz-icon icon-filter">Filter</span></button>
        </div>
        <form action="<?php echo Route::url('index.php?option=com_groups&cn=' . $this->group->get('cn') . '&active=publications/browse'); ?>" method="post" id="filter-form" enctype="multipart/form-data">
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
                                <li><a <?php echo ($this->sortBy == 'downloads') ? 'class="active"' : ''; ?> data-value="downloads" title="Downloads">Downloads</a></li>
                                <li><a <?php echo ($this->sortBy == 'views') ? 'class="active"' : ''; ?> data-value="views" title="Views">Views</a></li>
                                <li><a <?php echo ($this->sortBy == 'date') ? 'class="active"' : ''; ?> data-value="date" title="Date">Date</a></li>
                                <li><a <?php echo ($relevance_classes) ? 'class="' . $relevance_classes . '"' : ''; ?> data-value="score" title="Relevance">Relevance</a></li>
                            </ul>
                        </nav>
                    </div>
                        <?php
                        // Calling cards view
                        echo $this->view('cards', 'browse')
                            ->set('results', $this->results)
                            ->set('pageNav', $this->pageNav)
                            ->set('base', $this->base)
                            ->loadTemplate();
                        ?>
                </div>
            </div>
        </form>
    </div> <!-- .live-update-wrapper -->
</section>
