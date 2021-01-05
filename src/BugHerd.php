<?php


namespace IQnection\BugHerd;

use Bugherd\Client;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Environment;

class BugHerd
{
	use Configurable,
		Injectable;

	private static $webhook_path;

	protected $project_id;

	protected static $_client;
	protected static $_inst;

	public static function Client()
	{
		if (is_null(self::$_client))
		{
			self::$_client = new Client(Environment::getEnv('BUGHERD_API_KEY'));
			self::$_client->setCheckSslCertificate(false);
		}
		return self::$_client;
	}

	public static function inst()
	{
		if (is_null(self::$_inst))
		{
			self::$_inst = new self;
		}
		return self::$_inst;
	}

	public function isEnabled()
	{
		$enabled = Environment::getEnv('BUGHERD_ENABLED');
		return $enabled;
	}

	public function setProjectId($projectId)
	{
		$this->project_id = $projectId;
		return $this;
	}

	public function getProjectId()
	{
		if (is_null($this->project_id))
		{
			$this->project_id = Environment::getEnv('BUGHERD_PROJECT_ID');
		}
		return $this->project_id;
	}

	public function getTasks($params = [])
	{
		return self::Client()->api('task')->all($this->getProjectId(), $params);
	}

	public function getTask($id)
	{
		return self::Client()->api('task')->show($this->getProjectId(), $id);
	}

	public function updateTask($id, $params)
	{
		return self::Client()->api('task')->update($this->getProjectId(), $id, $params);
	}

	public function getWebhooks()
	{
		return self::Client()->api('webhook')->all();
	}

	public function createWebhook($event, $listenerUrl)
	{
		return self::Client()->api('webhook')->create(['project_id' => $this->getProjectId(),'event' => $event, 'target_url' => $listenerUrl]);
	}

	public function deleteWebhook($id)
	{
		return self::Client()->api('webhook')->remove($id);
	}

	public function getUsers()
	{
		return self::Client()->api('user')->all($this->getProjectId());
	}

	public function getColumns()
	{
		return self::Client()->api('column')->all($this->getProjectId());
	}
}