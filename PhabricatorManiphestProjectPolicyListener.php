<?php

final class PhabricatorManiphestProjectPolicyListener extends PhabricatorEventListener {

  protected function isSpecialProject($name) {
    return $name == 'Security' || $name == 'RT';
  }

  public function register() {
    $this->listen(PhabricatorEventType::TYPE_MANIPHEST_DIDEDITTASK);
  }

  public function handleEvent(PhutilEvent $event) {
    $task = $event->getValue('task');
    $this->enforceProjectPolicy($task);
  }

  protected function enforceProjectPolicy(ManiphestTask $task) {
    $project_phids = $task->getProjectPHIDs();

    $projects = id(new PhabricatorProjectQuery())
        ->withPHIDs($project_phids)
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->execute();

    foreach($projects as $project) {
      if ($this->isSpecialProject($project->getName())) {
        // if the task is associated with any of the special projects,
        // then we make the task's policy match the project's policy
        // does not currently handle the case where a task is assigned
        // to both special projects. Whichever comes last in the query
        // results will have it's policies assigned.
        $task
          ->setViewPolicy($project->getViewPolicy())
          ->setEditPolicy($project->getEditPolicy())
          ->save();
      }
    }
  }
}
