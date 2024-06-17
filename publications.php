<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();

use Hubzero\Pagination\Paginator;
use Components\Publications\Tables\MasterType;
use Components\Publications\Models\PubCloud;
use Components\Tags\Models\FocusArea;
use Components\Search\Helpers\SolrHelper;
use Components\Groups\Models\Orm\Group;

include_once Component::path('com_publications') . DS . 'models' . DS . 'publication.php';
include_once Component::path('com_groups') . DS . 'models' . DS . 'orm' . DS . 'group.php';
require_once PATH_APP . DS . 'libraries' . DS . 'Qubeshub' . DS . 'Plugin' . DS . 'Plugin.php';
require_once Component::path('com_search') . "/helpers/solr.php";

/**
 * Groups Plugin class for publications
 */
class plgGroupsPublications extends \Qubeshub\Plugin\Plugin
{
	
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically. Standard HUBzero plugin approach
	 *
	 * @var  boolean
	 */
	protected $_autoloadLanguage = true;
	
	/**
	 * Tags
	 * 
	 * @var object
	 */
	protected $_tags = null;

	/**
	 * Tags
	 * 
	 * @var object
	 */
	protected $_search = null;

	/**
	 * Active group
	 * 
	 * @var object
	 */
	protected $_group = null;

	/**
	 * Master type
	 * 
	 * @var object
	 */
	protected $_master_type = null;

	/**
	 * Loads the plugin language file
	 *
	 * @param   string   $extension  The extension for which a language file should be loaded
	 * @param   string   $basePath   The basepath to use
	 * @return  boolean  True, if the file has successfully loaded.
	 */
	public function loadLanguage($extension = '', $basePath = PATH_APP)
	{
		if (empty($extension))
		{
			$extension = 'plg_' . $this->_type . '_' . $this->_name;
		}
		
		$group = \Hubzero\User\Group::getInstance(Request::getCmd('cn'));
		if ($group && $group->isSuperGroup())
		{
			$basePath = PATH_APP . DS . 'site' . DS . 'groups' . DS . $group->get('gidNumber');
		}
		
		$lang = \App::get('language');
		return $lang->load(strtolower($extension), $basePath, null, false, true)
		|| $lang->load(strtolower($extension), PATH_APP . DS . 'plugins' . DS . $this->_type . DS . $this->_name, null, false, true)
		|| $lang->load(strtolower($extension), PATH_APP . DS . 'plugins' . DS . $this->_type . DS . $this->_name, null, false, true)
		|| $lang->load(strtolower($extension), PATH_CORE . DS . 'plugins' . DS . $this->_type . DS . $this->_name, null, false, true);
	}
	
	/**
	 * Return the alias and name for this category of content name. changed from on ProjectAreas to onGroupAreas
	 *
	 * @return     array
	 */
	public function &onGroupAreas()
	{
		$area = array(
			'name'             => 'publications',
			'title'            => Lang::txt('PLG_GROUPS_PUBLICATIONS'),
			'default_access'   => $this->params->get('plugin_access', 'members'),
			'display_menu_tab' => $this->params->get('display_tab', 1),
			'icon'             => 'f02d'
		);		
		return $area;
	}
	
	/**
	 * Return data on a group view (this will be some form of HTML)
	 *
	 * @param      object  $group      Current group
	 * @param      string  $option     Name of the component
	 * @param      string  $authorized User's authorization level
	 * @param      integer $limit      Number of records to pull
	 * @param      integer $limitstart Start of records to pull
	 * @param      string  $action     Action to perform
	 * @param      array   $access     What can be accessed
	 * @param      array   $areas      Active area(s)
	 * @return     array
	 */
	public function onGroup($group, $option, $authorized, $limit=0, $limitstart=0, $action='', $access, $areas=null)
	{
		if (empty($this->_group)) {
			$this->_group = $group;
		}
		$return = 'html';
		$active = $this->_name;
		
		// The output array we're returning
		$arr = array(
			'html'=>'',
			'metadata' => array(),
			'name' => $active
		);
		
		// get this area details
		$this_area = $this->onGroupAreas();
		
		// Check if our area is in the array of areas we want to return results for
		if (is_array($areas) && $limit)
		{
			if (!in_array($this_area['name'], $areas))
			{
				$return = 'metadata';
			}
		}
					
		if ($return == 'html')
		{
			//set group members plugin access level
			$group_plugin_acl = $access[$active];

			// Get the group members and managers
			$this->members = $group->get('members');
			$this->managers = $group->get('managers');

			// if set to nobody make sure cant access
			if ($group_plugin_acl == 'nobody')
			{
				$arr['html'] = '<p class="info">' . Lang::txt('GROUPS_PLUGIN_OFF', ucfirst($active)) . '</p>';
				return $arr;
			}
			
			// Check if guest and force login if plugin access is registered or members or managers
			if (User::isGuest() && ($group_plugin_acl == 'registered' || $group_plugin_acl == 'members' || $group_plugin_acl == 'managers'))
			{
				$area = Request::getWord('area', 'publications');
				$url = Route::url('index.php?option=com_groups&cn=' . $group->get('cn') . '&active=' . $active . '&area=' . $area);
			
				App::redirect(
					Route::url('index.php?option=com_users&view=login&return=' . base64_encode($url)),
					Lang::txt('GROUPS_PLUGIN_REGISTERED', ucfirst($active)),
					'warning'
				);
				return;
			}
			
			// check to see if user is member and plugin access requires members
			if (!in_array(User::get('id'), $this->members) && $group_plugin_acl == 'members' && $authorized != 'admin')
			{
				$arr['html'] = '<p class="info">' . Lang::txt('GROUPS_PLUGIN_REQUIRES_MEMBER', ucfirst($active)) . '</p>';
				return $arr;
			}

			// Check to see if user is manager and plugin access requires managers
			if (!in_array(User::get('id'), $this->managers) && $group_plugin_acl == 'managers' && $authorized != 'admin')
			{
				$arr['html'] = '<p class="info">' . Lang::txt('GROUPS_PLUGIN_REQUIRES_MANAGER', ucfirst($active)) . '</p>';
				return $arr;
			}		   

			// Get master type for group (if it exists)
			$this->database = App::get('db');
			$this->_master_type = new MasterType($this->database);
			$this->_master_type->loadByOwnerGroup($this->_group->get('gidNumber'));

			//option and paging vars
			$this->option = $option;
			$this->limitstart = $limitstart;
			$this->limit = $limit;
			$this->base = 'index.php?option=' . $this->option . '&cn=' . $this->_group->get('cn') . '&active=' . $this->_name;

			$path = Request::path();
			if (strstr($path, '/'))
			{
				$bits = $this->_parseUrl();
				// Section name
				if (isset($bits[0]) && trim($bits[0]))
				{
					if ($bits[0] == 'browse')
					{
						$action = 'browse';
					}
				} else {
					// Defaults
					if (Request::getVar('id', Request::getVar('alias', null)) && $this->_group->type == 3) {
						$action = 'view';
					} else {
						$action = 'browse';
					}
				}
			}
			$action = Request::getCmd('action', $action, 'post');

			switch ($action)
			{
				// Settings
				case 'browse':
					$arr['html'] .= $this->browseTask();
					break;
				case 'view':
					$arr['html'] .= $this->viewTask();
					break;
				default:
					$arr['html'] .= $this->browseTask();
					break;
			}
		}
				
		$arr['metadata']['count'] = 0;

		// Return the output
		return $arr;
	}
	
	/**
	 * Parse an SEF URL into its component bits
	 * stripping out the path leading up to the publications plugin
	 *
	 * @return  string
	 */
	private function _parseUrl()
	{
		static $path;

		if (!$path)
		{
			$path = Request::path();
			$path = str_replace(Request::base(true), '', $path);
			$path = str_replace('index.php', '', $path);
			$path = '/' . trim($path, '/');

			if ($path == '/groups/' . $this->_group->get('cn') . '/publications')
			{
				$path = array();
				return $path;
			}

			$path = ltrim($path, DS);
			$path = explode('/', $path);

			$paths = array();
			$start = false;
			foreach ($path as $bit)
			{
				if ($bit == 'groups' && !$start)
				{
					$start = true;
					continue;
				}
				if ($start)
				{
					$paths[] = $bit;
				}
			}
			if (count($paths) >= 2)
			{
				array_shift($paths); // Remove group cn
				array_shift($paths); // Remove 'publications'
			}
			$path = $paths;
		}

		return $path;
	}

	public function viewTask()
	{
		// Load neccesities for com_publications controller
		$lang = App::get('language');
		$lang->load('com_publications', \Component::path('com_publications') . DS . 'site');

		require_once \Component::path('com_publications') . DS .'models' . DS . 'publication.php';
		require_once \Component::path('com_publications') . DS .'site' . DS . 'controllers' . DS . 'publications.php';
		require_once \Component::path('com_publications') . DS .'helpers' . DS . 'html.php';
		
		// Set the request up to make it look like a user made the request to the controller
		Request::setVar('task', 'view');
		Request::setVar('option', 'com_publications');
		Request::setVar('active', Request::get('tab_active', 'about'));
		// Add some extra variables to let the tab view know we need a different base url
		Request::setVar('base_url', 'index.php?option=' . $this->option . '&cn=' . $this->_group->cn . '&active=publications');
		// Added a noview variable to indicate to the controller that we do not want it to try to display the view, simply build it
		Request::setVar('noview', 1);
		
		// Instantiate the controller and have it execute (base_path needed so view knows where template files are located)
		$newtest = new \Components\Publications\Site\Controllers\Publications(
			array('base_path' => \Component::path('com_publications') . DS . 'site',
				  'group' => $this->_group)
		);
		$newtest->execute();
		
		// Set up the return for the plugin 'view'
		return $newtest->view->loadTemplate();
	}
	
	public function browseTask() 
	{
		// Incoming
		$search = Request::getString('search', '');
		$sortBy = Request::getCmd('sortby', 'score');
		$limit = Request::getInt('limit', Config::get('list_limit'));
		$start = Request::getInt('limitstart', 0);
		$fl = Request::getString('fl', '');
		$fl = $fl ? explode(',', $fl) : array();
		$no_html = Request::getInt('no_html', 0);

		$gg = Group::oneOrFail($this->_group->gidNumber);

		// Get master type and focus areas
		$qubes_mtype_id = $this->_master_type->getType('qubesresource')->id;
		$mtype = $this->_master_type->id ? $this->_master_type->alias : null;
		$mtype_id = $this->_master_type->id ? $this->_master_type->id : $qubes_mtype_id;
		$by_group = !$mtype || ($this->_master_type->ownergroup != $this->_group->gidNumber);
		$fas = FocusArea::fromObject($mtype_id);

		// Get group ids (including children)
		$gids = array();
		$gids[] = $gg->gidNumber;
		$gids = array_merge($gids, $gg->children()->rows()->fieldsByKey('gidNumber'));
		$gids = array_map(function($gid) { return '"' . $gid . '"'; }, $gids);
		
		// Perform the search
		$solr = new SolrHelper;
		$filters = array_filter(array("fl" => $fl, "type" => $mtype, "gid" => $by_group ? $gids : null));
		$search_results = $solr->search($search, $sortBy, $limit, $start, $filters, $fas);
		$results = $search_results['results'];
		$numFound = $search_results['numFound'];
		$facets = $search_results['facets'];
		$leaves = $search_results['leaves'];
		$filters = $search_results['filters']; // Used for debugging for now

		// Convert results to publications
		$pubs = array();
		foreach ($results as $result) {
			$pid = explode('-', $result['id'])[1];
			$pub = \Components\Publications\Models\Orm\Publication::oneOrFail($pid);
			if ($vub = $pub->getActiveVersion()) {
				$vub->set('keywords', (new PubCloud($vub->get('id')))->render('list', array('type' => 'keywords', 'key' => 'raw_tag')));
				$pubs[] = $vub;
			}
		}

		// Initiate paging
		$pageNav = new Paginator(
			$numFound,
			$start,
			$limit
		);
		$pageNav->setAdditionalUrlParam('fl', count($fl) > 1 ? implode(",", $fl) : implode($fl));
		$pageNav->setAdditionalUrlParam('search', $search);

		$view = $this->view((!$no_html ? 'default' : 'cards'), 'browse')
					->set('results', $pubs)
					->set('group', $this->_group)
					->set('option', $this->option)
					->set('fas', $fas)
					->set('total', $numFound)
					->set('leaves', $leaves)
					->set('filters', $filters)
					->set('facets', $facets)
					->set('sortBy', $sortBy)
					->set('search', $search)
					->set('base', $this->base)
					->set('mtype', $mtype ? $mtype : 'qubesresource')
					->set('mtype_alias', $mtype ? $this->_master_type->type : 'QUBES')
					->set('pageNav', $pageNav)
					->setErrors($this->getErrors());
				
		// Return the output
		if (!$no_html) {
			return $view->loadTemplate();
		} else {
			$response = Array(
				'status' => \App::get('notification')->messages(),
				'facets' => array_map(function($facet) { return $facet->getValues(); }, $facets->getFacets()), // DEBUG
				'filters' => $filters, // DEBUG
				'leaves' => $leaves,
				'html' => [
					'cards' => $view->setLayout('cards')->loadTemplate(),
					'filters' => $view->setLayout('filters')->loadTemplate()
				]
			);

			// Ugly brute force method of cleaning output
			ob_clean();
			echo json_encode($response);
			exit();
		}
	}

	/**
	 * Retrieve records from database for items associated with this group
	 *
	 * @param      object  $group      Group that owns the records
	 * @param      unknown $authorized Authorization level
	 * @param      mixed   $limit      SQL record limit
	 * @param      integer $limitstart SQL record limit start
	 * @param      string  $sort       The field to sort records by
	 * @param      string  $access     Access level
	 * @return     mixed Returns integer when counting records, array when retrieving records
	 */
	public function getPublications($group, $authorized, $limit=0, $limitstart=0, $sort='date', $access='all')
	{
		// Do we have a group cn?
		if (!$group->get('cn'))
		{
			return array();
		}

		$database = App::get('db');
		$pubmodel = new Components\Publications\Models\Publication();

		$filters = array();
		$filters['state']         = 1;
		$filters['ignore_access'] = 1;

		if ($this->_master_type->id) {
			$filters['master_type'] = $this->_master_type->id;
		} else {
			$filters['group_owner'] = $group->get('gidNumber');
		}
		
		// Tags and keywords
		if ($this->_tags) {
			$filters['tag'] = $this->_tags;
		}
		$no_html = Request::getInt('no_html', 0);
		if ($no_html && isset($_POST['keywords']) && $_POST['keywords']) {
			$keywords = preg_split('/,\s*/', $_POST['keywords']);
			$filters['tag'] = array_merge($keywords, ($this->_tags ? $this->_tags : array()));
		}

		// Text search
		if ($this->_search) {
			$filters['search'] = $this->_search;
		}

		// Get count only?
		if (!$limit) {
			return $pubmodel->entries('count', $filters);
		}

		// Get records
		$filters['limit']         = $limit;
		$filters['start']         = $limitstart;
		$filters['sortby']        = $sort;
		$filters['sortdir']       = Request::getVar('sortdir', 'DESC');
		$filters['search']        = Request::getString('search', '');
		
		$rows = $pubmodel->entries('list', $filters);

		if ($rows)
		{
			// Loop through the results and set each item's HREF
			foreach ($rows as $key => $row)
			{
				//if we were a supergroup, this would be different
				if ($group->type == '3')
				{
					if ($row->alias)
					{
						$rows[$key]->href = Route::url('index.php?option=com_groups&cn=' . $group->cn . '&active=publications&alias=' . $row->alias);
					}
					else
					{
						if ($row->lastPublicRelease()) {
							$rows[$key]->href = Route::url('index.php?option=com_groups&cn=' . $group->cn . '&active=publications&id=' . $row->id . '&v=' . $row->lastPublicRelease()->version_number);
						} else {
							$rows[$key]->href = Route::url('index.php?option=com_groups&cn=' . $group->cn . '&active=publications&id=' . $row->id);
						}
					}
				}
				else
				{
					if ($row->alias)
					{
						$rows[$key]->href = Route::url('index.php?option=com_publications&alias=' . $row->alias);
					}
					else //most common case
					{
						$rows[$key]->href = Route::url('index.php?option=com_publications&id=' . $row->id);
					}
				}
			}
		}
		// Return the results
		return $rows;
	}
}