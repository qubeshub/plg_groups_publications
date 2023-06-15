<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();
$this->css()
     ->css('browse.css');

$no_html = Request::getInt('no_html', 0);
$n = 0;
?>

<?php if (!$no_html) { ?>
<div id="accord">
<?php } ?>
<?php foreach ($this->fas as $fa) { 
    $fa_tag = $fa->tag->tag;
    $facets = $this->facets->getFacet($fa_tag)->getValues();
    $filters = (array_key_exists($fa_tag, $this->filters) ? $this->filters[$fa_tag] : array());
    $disable = ($facets[$fa_tag] == 0);
    $n++;
 ?>

    <h5 class="accordion-section" for="tagfa-<?php echo $fa_tag ?>" <?php echo ($disable ? "style='display:none;'" : "") ?>>
        <button type="button" aria-expanded="<?php echo (!empty($filters) ? 'true' : 'false') ?>" class="accord-trigger" aria-controls="filter-panel-<?php echo $n; ?>" id="accord-<?php echo $n; ?>">
            <?php echo $fa->label; ?>
            <span class="facet-count" for="<?php echo $fa_tag ?>">(<?php echo $facets[$fa_tag]; ?>)</span>
            <span class="hz-icon icon-chevron-down"></span>
        </button>
    </h5>
    <div class="filter-panel" id="filter-panel-<?php echo $n; ?>" role="region" aria-labelledby="accord-<?php echo $n; ?>">
        <?php echo $fa->render('filter', array('root' => $fa_tag, 'filters' => $filters, 'facets' => $facets)); ?>
    </div>

<?php } ?>
<?php if (!$no_html) { ?>
</div>
<?php } ?>
