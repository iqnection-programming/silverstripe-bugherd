<?php


namespace IQnection\BugHerd;

use SilverStripe\Dev\BuildTask;
use IQnection\BugHerd\Model\Task;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;

class BugHerdTasks extends BuildTask
{
	private static $segment = 'bugherd';
	private static $events = [
		'task_create' => true,
		'task_update' => true,
		'task_destroy' => true,
	];

	public function run($request)
	{
		error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);
		if ($task = $request->requestVar('task'))
		{
			if (method_exists($this, $task))
			{
				$this->message('Running: '.$task);
				$this->{$task}($request);
			}
			else
			{
				$this->message('Task "'.$task.'" not found');
			}
		}
		$this->message('Complete');
	}

	/**
	* Downloads all tasks from BugHerd
	*
	* @param mixed $request
	* @return mixed
	*/
	public function getTasks($request)
	{
		$params = [];
		if ($status = $request->requestVar('status'))
		{
			$params['status'] = $status;
		}
		$tasks = BugHerd::inst()->getTasks($params);
		if (isset($tasks['error']))
		{
			$this->message('Error: '. $tasks['error']);
			return;
		}
		$this->message(array_keys($tasks));
		$tasks = $tasks['tasks'];
		$count = count($tasks);
		$this->message($count.' Tasks');
		foreach($tasks as $task)
		{
			$this->message($count.' - '.$task['description']);
			$count--;
			if (!$dbTask = Task::get()->Find('BugHerdID', $task['id']))
			{
				$dbTask = Task::create();
			}
			$dbTask->loadFromApi($task, false);
			$dbTask->write();
		}
	}

	/**
	* Updates the BugHerd status on all synced tasks
	*
	* @param mixed $request
	* @return mixed
	*/
	public function updateStatuses($request)
	{
		$tasks = BugHerd::inst()->getTasks();
		if (isset($tasks['error']))
		{
			$this->message('Error: '. $tasks['error']);
			return;
		}
		$this->message(array_keys($tasks));
		$tasks = $tasks['tasks'];
		$count = count($tasks);
		$this->message($count.' Tasks');
		foreach($tasks as $task)
		{
			$this->message($count.' - Updating '.$task['description']);
			$count--;
			if ( ($dbTask = Task::get()->Find('BugHerdID', $task['id'])) && ($dbTask->Status != $task['status']) )
			{
				$this->message('Updating');
				$dbTask->loadFromApi($task, false);
				$dbTask->write();
			}
		}
	}

	/**
	* Update the full task data
	*
	* Optional param id can be included to update a specific task
	*
	* @param mixed $request
	*/
	public function updateTasks($request)
	{
		$tasks = Task::get();
		$tasks = $tasks->Exclude('Status', ['done','closed']);
		if ($id = $request->requestVar('id'))
		{
			$tasks = $tasks->Filter('ID', $id);
		}
		$count = $tasks->Count();
		$this->message($count.' Tasks');
		foreach($tasks as $task)
		{
			$this->message($count.' - [ID:'.$task->ID.'] '.$task->Description);
			$count--;
			sleep(1);
			$taskData = BugHerd::inst()->getTask($task->BugHerdID);
			$taskData = $taskData['task'];
			if (!$taskData)
			{
				$this->message('No Task Data - May be Throttled');
				sleep(5);
				continue;
			}
			$task->loadFromApi($taskData);
			$task->write();
		}
	}

	/**
	* Parses the URL of the task, locates the page according to the URL,
	* and associates the task with that page
	*
	* Optional param id can be included to update a specific task
	*
	* @param mixed $request
	*/
	public function associatePages($request)
	{
		$tasks = Task::get();
		if ($id = $request->requestVar('id'))
		{
			$tasks = $tasks->Filter('ID', $id);
		}
		$count = $tasks->Count();
		$this->message('Associating '.$count.' Tasks');
		foreach($tasks as $task)
		{
			$this->message($count.' - '.$task->Description);
			$count--;
			if (!$page = $task->findIssuePage())
			{
				$this->message('Assiciated Page NOT FOUND');
				continue;
			}
			$task->PageID = $page->ID;
			$task->write();
		}
	}

	/**
	* Lists BugHerd Users and their data
	*
	* @param mixed $request
	*/
	public function getUsers($request)
	{
		$users = BugHerd::inst()->getUsers();
		$this->message($users);
	}

	/**
	* Displays the BugHerd task data for debugging
	*
	* @param mixed $request
	*/
	public function getTask($request)
	{
		if (!$id = $request->requestVar('id'))
		{
			$this->message('No ID provided');
			die();
		}
		$task = BugHerd::inst()->getTask($id);
		$this->message($task);
	}

	/**
	* Bulk assigns tasks to a specific user
	* Required user id to assign tasks to
	* Optional filter tasks by current status in SilverStripe database
	*
	* @param mixed $request
	*/
	public function assignTasks($request)
	{
		$userId = $request->requestVar('user');
		$status = $request->requestVar('status') ?: 'backlog';
		$client = BugHerd::inst();
		$tasks = Task::get();
		if ($id = $request->requestVar('id'))
		{
			$tasks = $tasks->Filter('ID', $id);
		}
		else
		{
			$tasks = $tasks->Filter('AssignedTo', [null, 'no-one']);
		}
		$count = $tasks->Count();
		$this->message($count.' Tasks to assign, to user id: '.$userId);
		sleep(1);
		foreach($tasks as $task)
		{
			$this->message($count.' - Assigning Task: '.$task->Description);
			$count--;
			if ( ($response = $client->updateTask($task->BugHerdID, ['assigned_to_id' => $userId])) && (isset($response['task'])) )
			{
				$task->loadFromApi($response['task']);
				$task->write();
			}
			usleep(1500000);
		}
	}

	/**
	* View current webhooks
	*
	* @param mixed $request
	*/
	public function viewWebhooks($request)
	{
		$client = BugHerd::inst();
		$currentWebHooks = $client->getWebhooks();
		$this->message($currentWebHooks);
	}

	/**
	* Delete all webhooks
	*
	* @param mixed $request
	*/
	public function deleteWebhooks($request)
	{
		$client = BugHerd::inst();
		$currentWebHooks = $client->getWebhooks();
		$currentWebHooks = $currentWebHooks['webhooks'];
		foreach($currentWebHooks as $currentWebHook)
		{
			$this->message('Deleting Webhook for Event: '.$currentWebHook['event']);
			$client->deleteWebhook($currentWebHook['id']);
		}
	}

	/**
	* Create webhooks to receive automatic updates
	*
	* @param mixed $request
	*/
	public function createWebhooks($request)
	{
		$client = BugHerd::inst();
		$currentWebHooks = $client->getWebhooks();
		$currentWebHooks = $currentWebHooks['webhooks'];
		$eventHooks = [];
		foreach($currentWebHooks as $currentWebHook)
		{
			$eventHooks[$currentWebHook['event']] = $currentWebHook;
		}
		$events = array_keys(array_filter($this->Config()->get('events')));
		foreach($events as $event)
		{
			if (array_key_exists($event, $eventHooks))
			{
				$this->message($event.' Hook Already Exists');
				continue;
			}
			$webhookPath = Director::absoluteUrl(Controller::join_links(BugHerd::Config()->get('webhook_path'), $event));
			$this->message('Creating Webhook for Event: '.$event.' At URL: '.$webhookPath);
			$client->createWebhook($event, $webhookPath);
		}
	}

	/**
	* View columns setup in BugHerd
	*
	* @param mixed $request
	*/
	public function getColumns($request)
	{
		$columns = BugHerd::inst()->getColumns();
		$this->message($columns);
	}

	/**
	* Prints a message to the screen
	*
	* @param mixed $message
	*/
	protected function message($message)
	{
		print_r($message);
		print "\n";
	}
}
