<?php

namespace IQnection\BugHerd\Control;

use SilverStripe\Control\Controller;
use IQnection\BugHerd\Model\Task;

class Listener extends Controller
{
	private static $allowed_actions = [
		'task_create',
		'task_update',
		'task_destroy'
	];

	protected function getTaskData($request)
	{
		$filePath = BASE_PATH.'/bugherd-updates.json';
		$issues = [];
		if (file_exists($filePath))
		{
			$issues = json_decode(file_get_contents($filePath), 1);
		}
		$taskData = json_decode($request->getBody(), 1);
		$taskData = $taskData['task'];
		$issues[] = $taskData;
		file_put_contents($filePath, json_encode($issues));
		return $taskData;
	}

	public function task_create($request)
	{
		$task = Task::create();
		$taskData = $this->getTaskData($request);
		$task->loadFromApi($taskData);
		$task->write();
		print '1';
		die();
	}

	public function task_update($request)
	{
		$taskData = $this->getTaskData($request);
		if (!$task = Task::get()->Find('BugHerdID', $taskData['id']))
		{
			$task = Task::create();
		}
		$task->loadFromApi($taskData);
		$task->write();
		print '1';
		die();
	}

	public function task_destroy($request)
	{
		$taskData = $this->getTaskData($request);
		if ($task = Task::get()->Find('BugHerdID', $taskData['id']))
		{
			$task->delete();
		}
		print '1';
		die();
	}
}
