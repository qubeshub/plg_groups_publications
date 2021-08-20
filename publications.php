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

include_once Component::path('com_publications') . DS . 'models' . DS . 'publication.php';
require_once PATH_APP . DS . 'libraries' . DS . 'Qubeshub' . DS . 'Plugin' . DS . 'Plugin.php';

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
		$active = 'publications';
		
		// The output array we're returning
		$arr = array(
			'html'=>''
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
		
		//set group members plugin access level
		$group_plugin_acl = $access[$active];
		
		//get the group members
		$members = $group->get('members');
		
		if ($return == 'html')
		{
			// if set to nobody make sure cant access
			if ($group_plugin_acl == 'nobody')
			{
				$arr['html'] = '<p class="info">' . Lang::txt('GROUPS_PLUGIN_OFF', ucfirst($active)) . '</p>';
				return $arr;
			}
			
			// check if guest and force login if plugin access is registered or members
			if (User::isGuest() && ($group_plugin_acl == 'registered' || $group_plugin_acl == 'members'))
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
			if (!in_array(User::get('id'), $members) && $group_plugin_acl == 'members' && $authorized != 'admin')
			{
				$arr['html'] = '<p class="info">' . Lang::txt('GROUPS_PLUGIN_REQUIRES_MEMBER', ucfirst($active)) . '</p>';
				return $arr;
			}
		}
		
		$database = App::get('db');
		
		// get a master type (if available)
		$this->_master_type = new MasterType($database);
		$this->_master_type->loadByOwnerGroup($group->get('gidNumber'));

		// Incoming paging vars
		$sort = Request::getVar('sort', 'date');
		if (!in_array($sort, array('date', 'title', 'ranking', 'rating')))
		{
			$sort = 'date';
		}
		$access = Request::getVar('access', 'all');
		if (!in_array($access, array('all', 'public', 'protected', 'private')))
		{
			$access = 'date';
		}
		
		$this->_tags = Request::getVar('tags', array());
		if (is_string($this->_tags)) {
			$this->_tags = preg_split('/,\s*/', $this->_tags);
		}

		$config = Component::params('com_publications');
		if ($return == 'metadata')
		{
			if ($config->get('show_ranking'))
			{
				$sort = 'ranking';
			}
			elseif ($config->get('show_rating'))
			{
				$sort = 'rating';
			}
		}
		
		if ($return == 'metadata')
		{
			$ls = -1;
		}
		else
		{
			$ls = $limitstart;
		}
		
		// Get the search results THIS is where search the database r
		$r = $this->getPublications(
			$group,
			$authorized,
			$limit,
			$limitstart,
			$sort,
			$access
		);
		$results = array($r);
		$total = count($results[0]);

		// Build the output
		switch ($return)
		{
			case 'html':
				// If we have a specific ID and we're a supergroup, serve a publication page inside supergroup template
				if (Request::getVar('id', Request::getVar('alias', null)) && $group->type == 3)
				{
					// Load neccesities for com_publications controller
					$lang = App::get('language');
					$lang->load('com_publications', \Component::path('com_publications') . DS . 'site');

					require_once \Component::path('com_publications') . DS .'models' . DS . 'publication.php';
					require_once \Component::path('com_publications') . DS .'site' . DS . 'controllers' . DS . 'publications.php';
					require_once \Component::path('com_publications') . DS .'helpers' . DS . 'html.php';
					
					// Set the request up to make it look like a user made the request to the controller
					Request::setVar('task', 'page');
					Request::setVar('option', 'com_publications');
					Request::setVar('active', Request::get('tab_active', 'about'));
					// Add some extra variables to let the tab view know we need a different base url
					Request::setVar('base_url', 'index.php?option=' . $this->option . '&cn=' . $group->cn . '&active=publications');
					// Added a noview variable to indicate to the controller that we do not want it to try to display the view, simply build it
					Request::setVar('noview', 1);
					
					// Instantiate the controller and have it execute (base_path needed so view knows where template files are located)
					$newtest = new \Components\Publications\Site\Controllers\Publications(
						array('base_path' => \Component::path('com_publications') . DS . 'site',
							  'group' => $this->_group)
					);
					$newtest->execute();
					
					// Set up the return for the plugin 'view'
					$arr['html'] = $newtest->view->loadTemplate();
					$arr['metadata']['count'] = $total;
				}
				else
				{			
					// Instantiate a vew
					$no_html = Request::getInt('no_html', 0);
					$view = $this->view((!$no_html ? 'default' : 'cards'), 'results');
				
					// Pass the view some info
					$view->option = $option;
					$view->group = $group;
					$view->authorized = $authorized;
					$view->results = $results;
					$view->active = $active;
					$view->limitstart = $limitstart;
					$view->limit = $limit;
					$view->total = $total;
					$view->sort = $sort;
					$view->access = $access;
					$view->tags = $this->_tags;

					// Initiate paging
					$pageNav = new Paginator(
						$total,
						$limitstart,
						$limit
					);
					$view->pageNav = $pageNav;
					
					foreach ($this->getErrors() as $error)
					{
						$view->setError($error);
					}

					// Return the output
					if (!$no_html) {
						$arr['metadata']['count'] = count($results[0]); // We need to clean this up - was $total, which should work
						$arr['html'] = $view->loadTemplate();
					} else {
						$response = Array(
							'status' => \App::get('notification')->messages(),
							'html' => $view->loadTemplate()
						);

						// Ugly brute force method of cleaning output
						ob_clean();
						echo json_encode($response);
						exit();
					}
				}
			break;
			
			case 'metadata':
				$arr['metadata']['count'] = count($results[0]); // We need to clean this up - was $total, which should work
			break;
		}
		
		// Return the output
		return $arr;
	}
	
	/**
	 * Retrieve records for items associated with this group
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

		$filters = array();
		$filters['state']         = 1;
		$filters['limit']         = Request::getInt('limit', Config::get('list_limit'));
		$filters['start']         = Request::getInt('limitstart', 0);
		$filters['sortby']        = Request::getVar('sortby', 'title');
		$filters['sortdir']       = Request::getVar('sortdir', 'ASC');
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
		
		$filters['sortby'] = 'title';
		$filters['limit'] = $limit;
		$filters['limitstart'] = $limitstart;

		// Get results
		$pubmodel = new Components\Publications\Models\Publication();
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