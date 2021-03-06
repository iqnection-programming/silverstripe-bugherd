<?php


namespace IQnection\BugHerd\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms;
use IQnection\BugHerd\Model\Task;
use IQnection\BugHerd\BugHerd;

class PageExtension extends DataExtension
{
	private static $has_many = [
		'BugHerdTasks' => Task::class
	];

	public function updateCMSFields($fields)
	{
		if (BugHerd::isEnabled())
		{
			$fields->addFieldToTab('Root.Bugherd', Forms\GridField\GridField::create(
				'BugHerdTasks',
				'BugHerd Tasks',
				$this->owner->BugHerdTasks(),
				Forms\GridField\GridFieldConfig_RecordViewer::create(20)
			));
		}
		return $fields;
	}

	public function BugHerdIssuesList()
	{
		$issues = [];
		foreach($this->owner->BugHerdTasks() as $tasks)
		{
			$issues[$tasks->Status]++;
		}
		foreach($issues as $status => &$count)
		{
			$count = $status.': '.$count;
		}
		return implode(', ', $issues);
	}

	public function BugHerdReportTitle()
	{
		return $this->owner->Breadcrumbs(10, true, false, true, ' > ');
	}
}
