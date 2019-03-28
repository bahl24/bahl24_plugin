<?php

defined( '_JEXEC' ) or die( 'Restricted access' );
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;

class PlgSearchBahl24 extends CMSPlugin
{
	
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	function onContentSearchAreas()
	{
		static $areas = array(
			'bahl24' => 'Bahl24'
		);
		return $areas;
	}

	
	function onContentSearch( $text, $phrase='', $ordering='', $areas=null )
	{
		$user	= Factory::getUser(); 
		$groups	= implode(',', $user->getAuthorisedViewLevels());


		if (is_array( $areas )) {
			if (!array_intersect( $areas, array_keys( $this->onContentSearchAreas() ) )) {
				return array();
			}
		}

		// Now retrieve the plugin parameters like this:
		$bahl24 = $this->params->get('bahl24', defaultsetting );

		// Use the PHP function trim to delete spaces in front of or at the back of the searching terms
		$text = trim( $text );

		// Return Array when nothing was filled in.
		if ($text == '') {
			return array();
		}

		
		$wheres = array();
		switch ($phrase) {

			// Search exact
			case 'exact':
				$text		= $this->db->Quote( '%'.$this->db->escape( $text, true ).'%', false );
				$wheres2 	= array();
				$wheres2[] 	= 'LOWER(a.name) LIKE '.$text;
				$where 		= '(' . implode( ') OR (', $wheres2 ) . ')';
				break;

			// Search all or any
			case 'all':
			case 'any':

			// Set default
			default:
				$words 	= explode( ' ', $text );
				$wheres = array();
				foreach ($words as $word)
				{
					$word		= $this->db->Quote( '%'.$this->db->escape( $word, true ).'%', false );
					$wheres2 	= array();
					$wheres2[] 	= 'LOWER(a.name) LIKE '.$word;
					$wheres[] 	= implode( ' OR ', $wheres2 );
				}
				$where = '(' . implode( ($phrase == 'all' ? ') AND (' : ') OR ('), $wheres ) . ')';
				break;
		}

		// Ordering of the results
		switch ( $ordering ) {

			//Alphabetic, ascending
			case 'alpha':
				$order = 'a.name ASC';
				break;

			// Oldest first
			case 'oldest':

			// Popular first
			case 'popular':

			// Newest first
			case 'newest':

			// Default setting: alphabetic, ascending
			default:
				$order = 'a.name ASC';
		}

		// Replace nameofplugin
		$section = Text::_( 'Bahl24' );

		// The database query; differs per situation! It will look something like this (example from newsfeed search plugin):
		$query	= $this->db->getQuery(true);
		$query->select('a.name AS title, "" AS created, a.link AS text, ' . $case_when."," . $case_when1);
				$query->select($query->concatenate(array($this->db->Quote($section), 'c.title'), " / ").' AS section');
				$query->select('"1" AS browsernav');
				$query->from('#__bahl24 AS a');
				$query->innerJoin('#__categories as c ON c.id = a.catid');
				$query->where('('. $where .')' . 'AND a.published IN ('.implode(',', $state).') AND c.published = 1 AND c.access IN ('. $groups .')');
				$query->order($order);

		// Set query
		$this->db->setQuery( $query, 0, $limit );
		$rows = $this->db->loadObjectList();

		// The 'output' of the displayed link. Again a demonstration from the newsfeed search plugin
		foreach($rows as $key => $row) {
			$rows[$key]->href = 'index.php?option=com_newsfeeds&view=newsfeed&catid='.$row->catslug.'&id='.$row->slug;
		}

	//Return the search results in an array
	return $rows;
	}
}