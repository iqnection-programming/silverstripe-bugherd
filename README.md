
# Silverstripe BugHerd

## Integrates the SilverStripe CMS with BugHerd tasks
[Still in development]

## Installation
`composer require iqnection/silverstripe-bugherd`

In your root .env file, add the following API credentials
```
BUGHERD_ENABLED="true"
BUGHERD_API_KEY="my-bugherd-api-key"
BUGHERD_PROJECT_ID="my-bugherd-project-id"
```

## Setup
Create the webhook to receive updates of new/changed/deleted tasks
Open up your SSH terminal and connect to your server
use thefolling command to run tasks...
`/path/to/site/root/vendor/bin/sake dev/tasks/bugherd task={bugherd-task}`

The following command tasks are available
#### Download/Sync tasks
To keep from downloading all tasks, you can include the optional {status} param to filter by task status current in SilverStripe
`/path/to/site/root/vendor/bin/sake dev/tasks/bugherd task=getTasks [status=backlog]`

#### Update Synced Task's Statuses
To keep from updating all tasks, you can include the optional {status} param to filter by task status current in SilverStripe
`/path/to/site/root/vendor/bin/sake dev/tasks/bugherd task=updateStatuses [status=backlog]`

#### Create Webhooks
Creates three webhooks to receive updates when tasks are created, updated, and deleted
`/path/to/site/root/vendor/bin/sake dev/tasks/bugherd task=createWebhooks`

You can disable certain hooks by updating the class stat
```
IQnection\BugHerd\BugHerdTasks:
  events:
    task_update: false
```

#### Delete Webhooks
`/path/to/site/root/vendor/bin/sake dev/tasks/bugherd task=deleteWebhooks`


## How to use
When enabled, all pages will receive a BugHerd tab to track issues on the particular page.

An additional report will be available to view your tasks by page, which makes it easier to group pages that have multiple tasks.
