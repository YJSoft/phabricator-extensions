<?php

class ProjectPolicyEnforcerAction extends HeraldCustomAction {

  public function appliesToAdapter(HeraldAdapter $adapter) {
    return $adapter instanceof HeraldManiphestTaskAdapter;
  }

  public function appliesToRuleType($rule_type) {
    switch ($rule_type) {
      case HeraldRuleTypeConfig::RULE_TYPE_GLOBAL:
      case HeraldRuleTypeConfig::RULE_TYPE_PERSONAL:
        return true;
      case HeraldRuleTypeConfig::RULE_TYPE_OBJECT:
      default:
        return false;
    }
  }

  public function getActionKey() {
    return "ProjectPolicy";
  }

  public function getActionName() {
    return "Make task visible only to members of the project:";
  }

  public function getActionType() {
    return "ProjectPolicyEnforcerAction";
  }

  public function applyEffect(
    HeraldAdapter $adapter,
    $object,
    HeraldEffect $effect) {

    $task = $adapter->getTask();
    //phlog(array($task, $object, $effect));

    $project_phids = $task->getProjectPHIDs();

    $projects = id(new PhabricatorProjectQuery())
        ->withPHIDs($project_phids)
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->execute();

    foreach($projects as $project) {
      if (strtolower($project->getName()) == strtolower($effect->getTarget())) {
        $task->setViewPolicy($project->getPHID())
             ->setEditPolicy($project->getPHID())
             ->save();

        $adapter->queueTransaction(
          id(new ManiphestTransaction())
            ->setTransactionType(PhabricatorTransactions::TYPE_VIEW_POLICY)
            ->setNewValue($project->getPHID()));

        return new HeraldApplyTranscript(
          $effect,
          true,
          pht(
            'Set task policy to: Members of Prroject %s',
            $project->getName()));
      }
    }
  }

}
