<?php


namespace IQnection\BugHerd\Reports;

use SilverStripe\Reports\Report;
use IQnection\BugHerd\Model\Task;
use SilverStripe\Forms;
use \Page;

class TasksReport extends Report
{
	public function title()
	{
		return 'BugHerd Issue Pages';
	}

	public function group()
    {
        return 'BugHerd';
    }

    public function getCMSFields()
    {
		$fields = parent::getCMSFields();
		$fields->push(Forms\LiteralField::create('_refresh','<style type="text/css">
#_BH_frame { display:none !important; }
</style>'));
		return $fields;
    }

	public function sourceRecords($params = null)
	{
		$tasks = Task::get();
		if ( (is_array($params)) && (count($params)) )
		{
			$tasks = $tasks->Filter('Status', $params['Status']);
		}
		if (!$tasks->Count())
		{
			return Page::get()->Filter('ID',0);
		}
		$pages = Page::get()->Filter('ID', $tasks->Column('PageID'));
		return $pages;
	}

	public function columns()
	{
		return [
			'AbsoluteLink' => [
                'title' => 'View',
                'formatting' => function ($value, $item) {
                    $Link = $item->AbsoluteLink();
                    return sprintf(
                        '<a href="%s" target="_blank">View</a>',
                        $Link
                    );
                }
            ],
			'BugHerdReportTitle' => [
				'title' => 'Page',
				'link' => true
			],
			'BugHerdIssuesList' => [
				'title' => 'Issues'
			],
		];
	}

	public function parameterFields()
    {
    	$status = array_unique(Task::get()->Column('Status'));
        return Forms\FieldList::create(
            Forms\DropdownField::create('Status', 'Status')
            	->setSource(array_combine($status, $status))
            	->setEmptyString('All')
        );
    }
}
