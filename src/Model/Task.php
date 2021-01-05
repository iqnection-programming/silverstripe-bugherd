<?php


namespace IQnection\BugHerd\Model;

use SilverStripe\ORM\DataObject;
use Page;

class Task extends DataObject
{
	private static $table_name = 'BugHerdTask';

	private static $db = [
		'BugHerdID' => 'Int',
		'Description' => 'Text',
		'Status' => 'Varchar(255)',
		'AssignedTo' => 'Varchar(255)',
		'RawData' => 'Text',
	];

	private static $has_one = [
		'Page' => \Page::class
	];

	private static $summary_fields = [
		'ID' => 'ID',
		'BugHerdID' => 'BugHerd ID',
		'Description' => 'Issue',
		'Status' => 'Status',
		'AssignedTo' => 'Assigned'
	];

	public function loadFromApi($data, $saveRawData = true)
	{
		$this->BugHerdID = $data['id'];
		$this->Description = $data['description'];
		$this->Status = $data['status'];
		$this->AssignedTo = 'no-one';
		if (isset($data['assigned_to']['dipslay_name']))
		{
			$this->AssignedTo = $data['assigned_to']['dipslay_name'];
		}
		if ($saveRawData)
		{
			$this->RawData = json_encode($data);
		}
		if ( (!$this->PageID) && ($page = $this->findIssuePage()) )
		{
			$this->PageID = $page->ID;
		}
		return $this;
	}

	public function findIssuePage()
	{
		$data = json_decode($this->RawData, 1);
		// attach to page
		$url = trim($data['url'], ' /');
		$urlParts = explode('/',$url);
		$parentId = 0;
		while($urlPart = array_shift($urlParts))
		{
			$urlPart = trim($urlPart, ' /');
			if (!$page = Page::get()->Filter(['ParentID' => $parentId, 'URLSegment' => $urlPart])->First())
			{
				return null;
			}
			$parentId = $page->ID;
		}
		return $page;
	}
}
